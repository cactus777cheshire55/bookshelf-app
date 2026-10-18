# BookShelf 書籍レビューアプリ

本システムは、書籍レビューアプリケーション「BookShelf」です。
ユーザーは書籍を登録・閲覧し、レビューの投稿やお気に入り登録ができます。
ジャンルによる分類やレビューへのいいね機能、平均評価に基づくランキング機能も備えています。
外部アプリケーション向けの公開API（JSON）も提供します。

## 作成者

高橋秀和

## 使用技術

- PHP 8.5
- Laravel 10.x
- MySQL 8.4
- Nginx
- Docker / Docker Compose / Laravel Sail
- Vite / Tailwind CSS 3.4
- Laravel Fortify（認証）
- phpMyAdmin
- Laravel Sanctum

## ER図

```mermaid
erDiagram
    users {
        bigint id PK
        string name
        string email "UK"
        string password       
        timestamp email_verified_at
        timestamp created_at
        timestamp updated_at
    }

    books {
        bigint id PK
        bigint user_id FK
        string title
        string author
        string isbn "UK"
        date published_date
        text description
        string image_url
        timestamp created_at
        timestamp updated_at
    }

    genres {
        bigint id PK
        string name "UK"
        timestamp created_at
        timestamp updated_at
    }

    book_genre {
        bigint id PK
        bigint book_id FK
        bigint genre_id FK
        timestamp created_at
        timestamp updated_at
    }

    reviews {
        bigint id PK
        bigint user_id FK
        bigint book_id FK
        tinyint rating
        text comment
        timestamp created_at
        timestamp updated_at
    }

    favorites {
        bigint id PK
        bigint user_id FK
        bigint book_id FK
        timestamp created_at
        timestamp updated_at
    }

    review_likes {
        bigint id PK
        bigint user_id FK
        bigint review_id FK
        timestamp created_at
        timestamp updated_at
    }

    reading_plans {
        bigint id PK
        bigint user_id FK
        bigint book_id FK
        date target_date
        date completed_at
        string status
        timestamp created_at
        timestamp updated_at
    }

    notifications {
        uuid id PK
        string type
        string notifiable_type
        bigint notifiable_id
        text data
        timestamp read_at
        timestamp created_at
        timestamp updated_at
    }

    users ||--o{ books : "creates"
    users ||--o{ reviews : "posts"
    users ||--o{ favorites : "likes"
    users ||--o{ review_likes : "likes"
    users ||--o{ reading_plans : "plans"
    users ||--o{ notifications : "receives"

    books ||--o{ reviews : "has"
    books ||--o{ favorites : "favorited_by"
    books ||--o{ book_genre : "has"
    books ||--o{ reading_plans : "planned_in"

    genres ||--o{ book_genre : "belongs_to"
    reviews ||--o{ review_likes : "liked_by"

```

## 開発環境URL

http://localhost

## 動作環境

- Docker
- Docker Compose

※ Windowsの場合はWSL2の利用を推奨します。

## 環境構築手順

1. **リポジトリをクローン**

    ```bash
    git clone https://github.com/cactus777cheshire55/bookshelf-app
    ```

2. **.envファイルの準備**

    `.env.example` をコピーして `.env` を作成します。

    ```bash
    cp .env.example .env
    ```

    `.env` ファイル内の以下のDB接続情報を確認・設定します。`.env.example` のデフォルト値はSail向けではないため、以下のように変更してください。

    ```ini
    DB_CONNECTION=mysql
    DB_HOST=mysql
    DB_PORT=3306
    DB_DATABASE=laravel
    DB_USERNAME=sail
    DB_PASSWORD=password
    ```

3. **Composer依存パッケージのインストール**

    プロジェクトの初回セットアップ時は、`vendor` ディレクトリが存在しないため `sail` コマンドを使用できません。
    以下のDockerコマンドを実行して、コンテナ内で `composer install` を実行します。

    ```bash
       docker run --rm \
        -u "$(id -u):$(id -g)" \
        -v "$(pwd):/var/www/html" \
        -w /var/www/html \
        laravelsail/php82-composer:latest \
        composer install --ignore-platform-reqs
    ```

4. **Laravel Sailの起動**

    以下のコマンドでDockerコンテナを起動します。

    ```bash
    ./vendor/bin/sail up -d
    ```

    > **エイリアスの設定（推奨）**
    >
    > 毎回 `./vendor/bin/sail` と入力するのは手間なので、エイリアスを設定すると便利です。
    >
    > ```bash
    > alias sail='[ -f sail ] && bash sail || bash vendor/bin/sail'
    > ```

5. **アプリケーションキーの生成**

    ```bash
    sail artisan key:generate
    ```

6. **データベースのマイグレーションと初期データ投入**

    以下のコマンドでテーブルを作成し、ダミーデータを投入します。

    ```bash
    sail artisan migrate:fresh --seed
    ```

    このコマンドの入力後、下記のエラーが表示されることがあります。

    ```bash
       Illuminate\Database\QueryException
      SQLSTATE[HY000] [1044] Access denied for user 'sail'@'%' to database 'bookshelf-app' (Connection: mysql, SQL: select table_name as `name`,         (data_length + index_length) as `size`, table_comment as `comment`, engine as `engine`, table_collation as `collation` from information_schema.tables where table_schema = 'bookshelf-app' and table_type in ('BASE TABLE', 'SYSTEM VERSIONED') order by table_name)

      at vendor/laravel/framework/src/Illuminate/Database/Connection.php:829
        825▕                     $this->getName(), $query, $this->prepareBindings($bindings), $e
        826▕                 );
        827▕             }
        828▕
      ➜ 829▕             throw new QueryException(
        830▕                 $this->getName(), $query, $this->prepareBindings($bindings), $e
        831▕             );
        832▕         }
        833▕     }

      +43 vendor frames

      44  artisan:35
          Illuminate\Foundation\Console\Kernel::handle()
    ```

    このエラーはコンテナ内にデータが残っており、エラーが生じているケースなどがあります。
    その場合は、以下のコマンドを順に実行して各コンテナを再起動して下さい。

    ```Bash
    sail down -v
    sail up -d　//コマンド実行後にSQLコンテナが立ち上がるまで時間がかかります。30秒ほどお待ちください。
    sail artisan migrate:fresh --seed
    ```

7. **フロントエンドのビルド**

    ```bash
    sail npm install
    sail npm install alpinejs
    sail npm run dev
    ```

    `npm run dev` は開発中は起動したままにしてください。

8. **アプリケーションへのアクセス**

    ブラウザで [http://localhost](http://localhost) にアクセスします。

## テスト実行

```bash
sail artisan test
```

カバレッジ付きで実行する場合:

```bash
sail artisan test --coverage
```

## 機能一覧

- ユーザー認証（登録、ログイン、ログアウト）
- 書籍登録・一覧取得・検索・単体取得
- 書籍詳細・編集・削除・
- レビュー投稿・編集・削除
- お気に入り登録・削除
- ジャンル登録・削除・編集
- ランキング表示
- 読書計画登録・一覧取得・編集
- 通知一覧・バッジ処理による実行

## APIエンドポイント一覧

- GET:/api/v1/books 書籍一覧を取得する
- GET:/api/v1/books/{book} 書籍詳細を取得する
- POST:/api/v1/books 書籍を新規登録する
- PUT:/api/v1/books/{book} 書籍を更新する
- DELETE:/api/v1/books/{book} 書籍を削除する

- ポストマンでURLに http://localhost/api/v1/login postﾒｿｯﾄﾞ Body タブをrawにしてタイプを JSON に設定し { "email": "yamada@example.com", "password": "password" } で送信してtokenを出してからAuthorizationタブのauth typeをBearer Tokenにしてtokenに張り付ける
