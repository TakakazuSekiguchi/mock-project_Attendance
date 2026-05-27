# mock-project_Attendance

## 概要
Laravelを用いて開発した勤怠アプリです。<br>

## 環境構築
※ Docker Desktop を起動した状態で以下の手順を実行してください。<br>

1. リポジトリをクローン<br>
git clone ~<br>
cd mock-project_Attendance<br>
docker-compose up -d --build<br>

2. composer install<br>
docker-compose exec php composer install<br>

3. `.env`を作成<br>
cd src<br>
cp .env.example .env<br>

- docker-compose.ymlのmysqlの箇所を参考に、設定値を変更してください。<br>
DB_HOST=mysql<br>
DB_DATABASE=laravel_db<br>
DB_USERNAME=laravel_user<br>
DB_PASSWORD=laravel_pass<br>

※「メール認証機能の設定」については後述いたしますので、<br>
内容を確認し設定値を変更してください。<br>

4. アプリキー生成<br>
docker-compose exec php php artisan key:generate<br>

5. マイグレーション<br>
docker-compose exec php php artisan migrate<br>
docker-compose exec php php artisan db:seed<br>

### メール認証機能
LaravelのEmail Verification機能を利用し、会員登録時にメール認証を必須としています。<br>
未認証ユーザーはログイン後も一部機能にアクセスできない仕様としています。<br>

開発環境では Mailtrap を使用し、送信メールの動作確認を行っています。<br>
`.env`ファイルについて、MailtrapのMy Sandbox内にあるUsername、Passwordを確認し、ご自身の設定値に変更してください。<br>
※Credentials の該当箇所を確認してください。<br>

- Mail設定例<br>
MAIL_MAILER=smtp<br>
MAIL_HOST=sandbox.smtp.mailtrap.io<br>
MAIL_PORT=2525<br>
MAIL_USERNAME=your_username<br>
MAIL_PASSWORD=your_password<br>
MAIL_ENCRYPTION=tls<br>
MAIL_FROM_ADDRESS=noreply@example.com<br>
MAIL_FROM_NAME="${APP_NAME}"<br>

## 画面定義

## 使用技術（実行環境）
- PHP 8.4.12
- Laravel 8.83.29
- MySQL 8.0.26
- nginx 1.21.1
- Docker
- Mailtrap
- PHPUnit

## ER図
![ER図](./index.drawio.png)

## テーブル設計方針

## ログイン情報（動作確認用アカウント）
### 管理者ユーザー
- メールアドレス：admin@example.com
- パスワード：admin000

### 一般ユーザー1
- メールアドレス：user1@example.com
- パスワード：aaaa1111

### 一般ユーザー2
- メールアドレス：user2@example.com
- パスワード：bbbb2222

※ 上記アカウントは `php artisan db:seed` 実行時に作成されます。<br>
※ すべてのユーザーについて、メール認証済みの状態で作成されています。<br>

## テスト
Featureテスト、Unitテストを中心に実装しています。<br>


### テスト環境構築
1. データベースを作成<br>
- rootユーザーで MySQL にログイン<br>
docker compose exec mysql bash<br>
mysql -u root -p<br>
※パスワードは、docker-compose.ymlファイルのMYSQL_ROOT_PASSWORD:に設定されている値を入力してください。<br>

- demo_test というデータベースを作成<br>
CREATE DATABASE demo_test;<br>
SHOW DATABASES;<br>
※SHOW DATABASES;入力後、demo_testが作成されていれば成功です。<br>

2. `.env` をコピーして `.env.testing` を作成<br>
cd src<br>
cp .env .env.testing<br>

3. `.env.testing` のAPP_ENVとAPP_KEY=を以下のように変更<br>
APP_ENV=test<br>
APP_KEY=<br>

※ APP_KEYはテスト用に再生成するため、一度空にしてください。<br>
その後、後述のコマンド（key:generate）でテスト用キーを生成します。<br>

4. `.env.testing` のDB設定を以下のように変更<br>
DB_CONNECTION=mysql_test<br>
DB_DATABASE=demo_test<br>
DB_USERNAME=root<br>
DB_PASSWORD=root<br>

※【重要】本番用データベースと分離するため、テスト専用DBを使用しています。<br>
必ず『DB_DATABASE=demo_test』に書き換えるようお願いいたします。<br>

### テスト用データベースを作成
docker-compose exec php php artisan key:generate --env=testing<br>
docker-compose exec php php artisan migrate:fresh --env=testing<br>

### テスト実行
docker-compose exec php php artisan test<br>

※ テストでは RefreshDatabase を使用し、各テスト実行ごとにDBをリセットしています。<br>

