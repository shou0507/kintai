# kintai
## 環境構築
**Dockerビルド**
1. `git clone git@github.com:shou0507/kintai.git`
2. DockerDesktopアプリを立ち上げる
3. `docker-compose up -d --build`

> *MacのM1・M2チップのPCの場合、`no matching manifest for linux/arm64/v8 in the manifest list entries`のメッセージが表示されビルドができないことがあります。
エラーが発生する場合は、docker-compose.ymlファイルの「mysql」内に「platform」の項目を追加で記載してください*
``` bash
mysql:
    platform: linux/x86_64(この文追加)
    image: mysql:8.0.26
    environment:
```

**Laravel環境構築**
1. `docker-compose exec php bash`
2. `composer install`
3. 「.env.example」ファイルを 「.env」ファイルに命名を変更。または、新しく.envファイルを作成
4. .envに以下の環境変数を追加
``` text
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_pass
```
cp .env.example .env
5. アプリケーションキーの作成
``` bash
php artisan key:generate
```

6. マイグレーションの実行
``` bash
php artisan migrate
```

7. シーディングの実行
``` bash
php artisan db:seed
```

## 使用技術(実行環境)
- PHP8.3.0
- Laravel8.83.27
- MySQL8.0.26
- Mailtrap（メール送信テスト）
- Stripe（クレジットカード決済）

## ER図
![ER図](/Users/sugiyamashou/kintai/src/.drawio.png)
## URL
- 開発環境：http://localhost/
- phpMyAdmin:：http://localhost:8080/

## メール送信設定（Mailtrap）

開発環境では Mailtrap を使用してメール送信をテストしています。

1. [Mailtrap](https://mailtrap.io/) にサインアップし、Inbox を作成します。
2. Inbox の「Integration」タブから Laravel / PHP 用の接続情報を確認します。
3. `.env` に以下のように設定します（値は Mailtrap の画面からコピーしてください）。

```text
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=（Mailtrap の Username）
MAIL_PASSWORD=（Mailtrap の Password）
MAIL_ENCRYPTION=tls

MAIL_FROM_ADDRESS=test@example.com
MAIL_FROM_NAME="MyApp"
