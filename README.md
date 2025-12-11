# coachtech フリマ

フリーマーケットアプリケーション

## 環境構築

### 必要なソフトウェア

- **Git**: リポジトリのクローン用
- **Docker**: コンテナ実行環境
- **Docker Compose**: マルチコンテナ管理

**注意**: PHP, Laravel, MySQL, Node.js 等は Docker コンテナ内に含まれているため、ローカルへのインストールは不要です。

### Docker ビルド

1. リポジトリをクローン

```bash
git clone https://github.com/pon02/coachtech-flea-market.git
cd coachtech-flea-market
```

2. Docker コンテナをビルド・起動

```bash
docker compose up -d --build
```

3. コンテナの起動確認

```bash
docker compose ps
```

### Laravel 環境構築

1. PHP コンテナに入る

```bash
docker compose exec php bash
```

2. 依存関係をインストール

```bash
composer install
npm install
```

3. 環境変数ファイルをコピー

```bash
cp .env.example .env
```

4. アプリケーションキーを生成

```bash
php artisan key:generate
```

5. データベースをマイグレーション・シード

```bash
php artisan migrate --seed
```

6. ストレージリンクを作成

```bash
php artisan storage:link
```

7. フロントエンドアセットをビルド

```bash
npm run dev
```

## 使用技術（実行環境）

### バックエンド

- **PHP**: 8.1
- **Laravel**: 9.x
- **MySQL**: 8.0

### フロントエンド

- **HTML/CSS/JavaScript**
- **Laravel Mix**

### 認証

- **Laravel Fortify**: メール認証機能

### 決済

- **Stripe**: クレジットカード決済

### 開発環境

- **Docker**: コンテナ化
- **Docker Compose**: マルチコンテナ管理
- **Nginx**: Web サーバー
- **phpMyAdmin**: データベース管理
- **MailHog**: メール送信テスト

### テスト

- **PHPUnit**: 単体・統合テスト

## ER 図

![ER図](img/ER図.png)

## URL

### 開発環境

- **アプリケーション**: http://localhost
- **phpMyAdmin**: http://localhost:8080
- **MailHog**: http://localhost:8025

### 主要な機能 URL

- **トップページ**: http://localhost/
- **会員登録**: http://localhost/register
- **ログイン**: http://localhost/login
- **商品出品**: http://localhost/sell
- **マイページ**: http://localhost/mypage

## 機能一覧

### 認証機能

- 会員登録
- メール認証
- ログイン・ログアウト
- プロフィール設定

### 商品機能

- 商品一覧表示
- 商品詳細表示
- 商品検索
- 商品出品
- カテゴリ分類

### 購入機能

- 商品購入
- 決済（コンビニ決済・Stripe 決済）
- 配送先設定
- 購入履歴

### ソーシャル機能

- いいね機能
- コメント機能
- マイリスト

## 動作確認用ユーザー

アプリケーションの動作確認のため、Seeder で作成している以下のデモアカウントをご利用ください：

| ユーザー名 | メールアドレス     | パスワード |
| ---------- | ------------------ | ---------- |
| tanaka     | tanaka@example.com | 12345678   |
| yamada     | yamada@example.com | 12345678   |
| suzuki     | suzuki@example.com | 12345678   |
| sato       | sato@example.com   | 12345678   |
| takeda     | takeda@example.com | 12345678   |

**※注意**: これらはデモ用アカウントです。本番環境では使用しないでください。

## テスト

```bash
# 全テスト実行
docker compose exec php php artisan test

# Featureテストのみ実行
docker compose exec php php artisan test --testsuite=Feature

# 特定のテストファイルを実行
docker compose exec php php artisan test tests/Feature/Auth/LoginTest.php
```
