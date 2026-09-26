# FemiwikiCrawlingBlocker is archived.

Femiwiki no longer uses it. It never worked in production: its special page hook does not check whether the user is logged in, so turning it on put a CAPTCHA in front of every special page for everyone, including login, account creation and search ([femiwiki#457](https://github.com/femiwiki/femiwiki/issues/457)). It stayed switched off in production until it was removed ([infra#469](https://github.com/femiwiki/infra/pull/469)). And as a MediaWiki extension it runs only after PHP has already taken the request, which is where the cost lies. Femiwiki now refuses crawlers in front of PHP, in the [Caddy configuration](https://github.com/femiwiki/infra/blob/3ee4edfc68028f6f9d93f98f23910705b50afdb5/docker/res/Caddyfile).

페미위키는 더 이상 이 확장을 쓰지 않습니다. 운영에서 제대로 동작한 적이 없습니다. 특수 문서 훅이 로그인 여부를 확인하지 않아서, 켜면 로그인한 사용자까지 로그인, 계정 만들기, 검색을 포함한 모든 특수 문서에서 CAPTCHA를 보게 됩니다([femiwiki#457](https://github.com/femiwiki/femiwiki/issues/457)). 그래서 운영에서는 제거될 때까지 꺼져 있었습니다([infra#469](https://github.com/femiwiki/infra/pull/469)). 또 미디어위키 확장이라 PHP가 이미 요청을 받은 뒤에야 돌기 때문에 비용을 줄일 수 없습니다. 지금은 PHP 앞단인 [Caddy 설정](https://github.com/femiwiki/infra/blob/3ee4edfc68028f6f9d93f98f23910705b50afdb5/docker/res/Caddyfile)에서 크롤러를 거절합니다.

# FemiwikiCrawlingBlocker

## Support language

- DE, EN, JA, KO, ZH

## License

- MIT

## Description

### 한국어

페미위키에 최적화된 크롤링 봇을 방어하는 블로커입니다.

코드 레퍼런스

- https://github.com/mywikis/CrawlerProtection/

### English

A MediaWiki extension to block bots using reCAPTCHA.

Code Reference

- https://github.com/mywikis/CrawlerProtection/

### 日本語

フェミウィキに最適化されたクローリングボットを防御するブロッカーです。

コードリファレンス

- https://github.com/mywikis/CrawlerProtection/
