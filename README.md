# FemiwikiCrawlingBlocker is archived.

Femiwiki no longer uses it. It never blocked crawlers reliably, and as a MediaWiki extension it runs only after PHP has already taken the request, which is where the cost lies. Femiwiki now refuses crawlers in front of PHP, in the Caddy configuration of [femiwiki/infra](https://github.com/femiwiki/infra/blob/main/docker/res/Caddyfile).

페미위키는 더 이상 이 확장을 쓰지 않습니다. 크롤러를 제대로 막은 적이 없고, 미디어위키 확장이라 PHP가 이미 요청을 받은 뒤에야 돌기 때문에 비용을 줄일 수 없습니다. 지금은 PHP 앞단인 [femiwiki/infra](https://github.com/femiwiki/infra/blob/main/docker/res/Caddyfile)의 Caddy 설정에서 크롤러를 거절합니다.

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
