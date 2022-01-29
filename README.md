# sat_web

## 前提
Amazon Linux2での作業を前提としています

## 環境構築

dokcer のインストール方法 & 自動起動化
```
sudo yum install -y docker
sudo systemctl start docker
sudo systemctl enable docker
sudo usermod -a -G docker ec2-user
```

docker-compose インストール方法
```
sudo curl -L "https://github.com/docker/compose/releases/download/1.29.2/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
```


### ソースコードの設置
```
git@github.com:VolatileMint/sat_web.git
```

### ビルドと起動
```
docker-compose build
docker-compose up
```

### テーブルの作成
docker-compose 起動中に以下のコマンドでMySQLのCLIクライアントを起動してください。
```
docker exec -it mysql mysql techc
```
テーブルを作成するSQLは以下の通りです。
ユーザー情報テーブル
```
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `icon_filename` text COLLATE utf8mb4_unicode_ci,
  `introduction` text COLLATE utf8mb4_unicode_ci,
  `cover_filename` text COLLATE utf8mb4_unicode_ci,
  `birthday` date DEFAULT NULL,
  PRIMARY KEY (`id`)
);
```
ユーザーのフォロー関係テーブル
```
CREATE TABLE `user_relationships` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `follower_user_id` int(10) unsigned NOT NULL,
  `followee_user_id` int(10) unsigned NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);
```

掲示板投稿内容テーブル
```
CREATE TABLE `bbs_entries` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);
```
掲示板投稿画像テーブル
```
CREATE TABLE `bbs_images` (
  `id` int(11) NOT NULL,
  `image_filename` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
);
```

## 動作確認

http://IPアドレス/login.php にアクセスして動作を確認してください。
構築手順は以上です。
