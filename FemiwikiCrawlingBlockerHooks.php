<?php
/**
 * FemiwikiCrawlingBlocker
 * @license MIT
 *
 * A MediaWiki extension to block bots using reCAPTCHA.
 * Reference: https://github.com/mywikis/CrawlerProtection/
 */

namespace FemiwikiCrawlingBlocker;

use Config;
use RequestContext;
use User;
use WebRequest;

class FemiwikiCrawlingBlockerHooks implements
	\MediaWiki\Hook\MediaWikiPerformActionHook,
	\MediaWiki\SpecialPage\Hook\SpecialPageBeforeExecuteHook
	{
	private const COOKIE_NAME = "FemiwikiCrawlingBlockerPassed";
	/** 1일 (1 day) */
	private const COOKIE_EXPIRY = 86400;
	/** @var Config */
	private $config;

	/**
	 * @param Config $config
	 */
	public function __construct( Config $config ) {
		$this->config = $config;
	}

	/**
	 * MediaWiki 액션 처리 시 CAPTCHA 검증을 수행합니다.
	 *
	 * Perform CAPTCHA verification during MediaWiki actions.
	 *
	 * @inheritDoc
	 */
	public function onMediaWikiPerformAction(
		$output,
		$article,
		$title,
		$user,
		$request,
		$ActionEntryPoint
	) {
		if ( !$this->config->get( 'FemiwikiCrawlingBlockerEnabled' ) ) {
			return true;
		}
		$type = $request->getVal( "type" );
		$action = $request->getVal( "action" );
		$diffId = (int)$request->getVal( "diff" );
		$oldId = (int)$request->getVal( "oldid" );

		if (
			!$user->isRegistered() &&
			( $type === "revision" ||
				$action === "history" ||
				$diffId > 0 ||
				$oldId > 0 )
		) {
			return self::captchaExec( $request, $user );
		}

		return true;
	}

	/**
	 * SpecialPage 실행 전 CAPTCHA 검증을 수행합니다.
	 *
	 * Perform CAPTCHA verification before executing a SpecialPage.
	 *
	 * @inheritDoc
	 */
	public function onSpecialPageBeforeExecute(
		$specialPage,
		$subPage
	) {
		if ( !$this->config->get( 'FemiwikiCrawlingBlockerEnabled' ) ) {
			return true;
		}
		$user = $specialPage->getContext()->getUser();
		$request = RequestContext::getMain()->getRequest();

		return self::captchaExec( $request, $user );
	}

	/**
	 * CAPTCHA 검증 로직을 실행합니다.
	 *
	 * Execute the CAPTCHA verification logic.
	 *
	 * @param WebRequest $request
	 * @param User $user
	 * @return bool
	 */
	private static function captchaExec( WebRequest $request, User $user ): bool {
		$requestUrl = $request->getRequestURL();
		$verifyCode = self::generateVerifyCode();

		if (
			isset( $_COOKIE[self::COOKIE_NAME] ) &&
			hash_equals( $_COOKIE[self::COOKIE_NAME], $verifyCode )
		) {
			return true;
		}

		if ( $request->wasPosted() ) {
			$token = $request->getVal( "g-recaptcha-response" );
			if ( self::verifyRecaptcha( $token ) ) {
				setcookie( self::COOKIE_NAME, $verifyCode, [
					"expires" => time() + self::COOKIE_EXPIRY,
					"path" => "/",
					"httponly" => true,
					"secure" => true,
					"samesite" => "Lax",
				] );

					header( "Location: " . $requestUrl );
					exit();
			}
		}

		http_response_code( 429 );
		return self::outputRecaptchaPage( $requestUrl );
	}

	/**
	 * CAPTCHA 검증용 해시를 생성합니다.
	 *
	 * Generate the verification hash for CAPTCHA.
	 *
	 * @return string
	 */
	private static function generateVerifyCode(): string {
		global $wgReCaptchaSiteKey, $wgReCaptchaSecretKey;

		$keys = [
			// 날짜가 바뀌면 다시 시작
			"DATE" => date( "Y-m-d" ),
			"HOSTNAME" => $_SERVER["HTTP_HOST"] ?? "unknown",
			"REMOTE_ADDR" => $_SERVER["REMOTE_ADDR"] ?? "unknown",
			"REMOTE_HOST" => $_SERVER["REMOTE_HOST"] ?? "unknown",
			"SERVER_SOFTWARE" => $_SERVER["SERVER_SOFTWARE"] ?? "unknown",
			"HTTP_HOST" => $_SERVER["HTTP_HOST"] ?? "HTTP_HOST unknown",
			"HTTP_X_FORWARDED_HOST" =>
				$_SERVER["HTTP_X_FORWARDED_HOST"] ??
				"HTTP_X_FORWARDED_HOST unknown",
			"DOCUMENT_ROOT" =>
				$_SERVER["DOCUMENT_ROOT"] ?? "DOCUMENT_ROOT unknown",
			"HTTP_ACCEPT_LANGUAGE" =>
				$_SERVER["HTTP_ACCEPT_LANGUAGE"] ??
				"HTTP_ACCEPT_LANGUAGE unknown",
			"PHP_CPPFLAGS" =>
				$_SERVER["PHP_CPPFLAGS"] ?? "PHP_CPPFLAGS unknown",
			"WG_DB_USER" => $_SERVER["WG_DB_USER"] ?? "WG_DB_USER not set",
			"MEDIAWIKI_SERVER" =>
				$_SERVER["MEDIAWIKI_SERVER"] ?? "MEDIAWIKI_SERVER not set",
			"SITE_KEY" => $wgReCaptchaSiteKey,
			"SECRET_KEY" => $wgReCaptchaSecretKey,
			"GPG_KEYS" => $_SERVER["GPG_KEYS"] ?? "GPG_KEYS not set",
			"mtime" =>
				filemtime( realpath( __DIR__ . "/../../LocalSettings.php" ) ) ??
				"LocalSettings.php not found",
		];

		$seed = sha1( implode( "salt-[RecaptchaBlocker]-salt", $keys ) );

		foreach ( $keys as $k => $v ) {
			$suffix = empty( $v ) ? "empty" : $v;
			$seed .= sha1(
				$seed . "salt-[RecaptchaBlocker]-salt-{$k}-{$suffix}"
			);
		}

		return sha1( "RecaptchaBlocker-Check-Key" . $seed );
	}

	/**
	 * Google reCAPTCHA API를 호출하여 응답을 검증합니다.
	 *
	 * Verify the response by calling the Google reCAPTCHA API.
	 *
	 * @param string $token
	 * @return bool
	 */
	private static function verifyRecaptcha( string $token ): bool {
		global $wgReCaptchaSecretKey;

		$reCaptchaPayload = http_build_query( [
			"secret" => $wgReCaptchaSecretKey,
			"response" => $token,
		] );

		$ch = curl_init( "https://www.google.com/recaptcha/api/siteverify" );
		curl_setopt_array( $ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $reCaptchaPayload,
			CURLOPT_TIMEOUT => 5,
		] );

		$response = curl_exec( $ch );
		curl_close( $ch );

		if ( !$response ) {
			return false;
		}

		$result = json_decode( $response, true );
		return $result["success"] ?? false;
	}

	/**
	 * CAPTCHA 페이지를 출력하여 봇 검증을 수행합니다.
	 *
	 * Output the CAPTCHA verification page.
	 *
	 * @param string $currentUrl
	 * @return bool
	 */
	private static function outputRecaptchaPage( string $currentUrl ): bool {
		global $wgReCaptchaSiteKey;
		header( "Content-Type: text/html; charset=utf-8" );

		$currentUrl = htmlspecialchars( $currentUrl );
		$siteKey = $wgReCaptchaSiteKey;
		$translate = "wfMessage";

		echo <<<HTML
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="utf-8">
<title>{$translate("recaptcha-blocker-title")->text()}</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script src="https://www.google.com/recaptcha/api.js?render={$siteKey}"></script>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
html, body { background: #263038; height: 100%; }
.loader { width: 48px; height: 48px; border: 5px solid #fff; border-bottom-color: transparent;
border-radius: 50%; animation: spin 1s linear infinite; margin: 20px auto; }
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
#container { display: flex; align-items: center; justify-content: center; height: 100%; }
#box { background: rgba(0,0,0,0.3); border-radius: 10px; padding: 70px 20px; text-align: center; width: 300px; }
#box .message { color: #fff; font-size: 20px; margin-bottom: 40px; }
</style>
</head>
<body>
<div id="container">
  <div id="box">
    <div class="message">
        {$translate("recaptcha-blocker-please-wait")->text()}
        <br>
        {$translate("recaptcha-blocker-please-wait-description")->text()}
    </div>
    <div class="loader"></div>
    <form method="post">
      <input type="hidden" name="original-url" value="{$currentUrl}">
      <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
    </form>
  </div>
</div>
<script>
grecaptcha.ready(function() {
  grecaptcha.execute('{$siteKey}', { action: 'homepage' }).then(function(token) {
    document.getElementById('g-recaptcha-response').value = token;
    document.forms[0].submit();
  });
});
</script>
</body>
</html>
HTML;

		exit();
	}
}
