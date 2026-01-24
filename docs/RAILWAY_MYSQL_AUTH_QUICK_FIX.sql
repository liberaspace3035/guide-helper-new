-- Railway MySQL認証方式変更用SQLスクリプト
-- MySQLコンソールで実行してください

-- 1. 現在のユーザーと認証方式を確認
SELECT user, host, plugin FROM mysql.user WHERE user = 'root';

-- 2. 認証方式をmysql_native_passwordに変更
-- 注意: '現在のパスワード'を実際のパスワードに置き換えてください
-- パスワードはRailwayのMySQLサービスの「Variables」タブの「MYSQLPASSWORD」から確認できます
ALTER USER 'root'@'%' IDENTIFIED WITH mysql_native_password BY '現在のパスワード';

-- 3. 権限を再読み込み
FLUSH PRIVILEGES;

-- 4. 変更を確認（pluginがmysql_native_passwordになっていることを確認）
SELECT user, host, plugin FROM mysql.user WHERE user = 'root';

