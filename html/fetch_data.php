<?php
// エラーメッセージ表示のため、デバッグモードを有効にする
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ログファイルへのパス（同じ階層のLOGフォルダ内）
$logFile = __DIR__ . '/LOG/error_log.txt';

// ログをファイルに書き込む関数
function logError($message) {
    global $logFile;
    $timestamp = date("Y-m-d H:i:s");
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// データベース接続設定
$host = 'localhost';  // ホスト名
$db = 'raceresult';     // データベース名
$user = 'keiba';       // ユーザー名
$pass = 'baken';           // パスワード

// MySQLデータベースに接続
$conn = new mysqli($host, $user, $pass, $db);

// 接続チェック
if ($conn->connect_error) {
    $errorMessage = "データベース接続失敗: " . $conn->connect_error;
    logError($errorMessage);  // エラーをログファイルに保存
    die($errorMessage);  // エラーメッセージを画面にも表示
}

// パラメータとして送信された馬名を取得
$horse_name = isset($_GET['horse_name']) ? $_GET['horse_name'] : '';

// SQL文を編集してデータを取得（馬名で絞り込み）
$sql = "SELECT X.年 * 10000 + X.月 * 100 + X.日 AS 日付, X.場所 AS 場所, X.レース名 AS レース名, X.確定着順 AS 着順, X.芝ダ AS 芝ダート, X.距離 AS 距離, Z.馬名 AS '一着馬', Y.単勝配当１ AS 一着単勝配当
FROM race_data X
INNER JOIN 配当a Y
ON X.レース番号 = Y.レース番号 AND X.年 = Y.年 AND X.月 = Y.月 AND X.日 = Y.日 AND X.場所 = Y.場所
INNER JOIN race_data Z
ON X.レース番号 = Z.レース番号 AND X.年 = Z.年 AND X.月 = Z.月 AND X.日 = Z.日 AND X.場所 = Z.場所 AND Z.確定着順 = 1
ORDER BY X.年, X.月, X.日";

// 馬名が入力されている場合はSQL文を絞り込む
if (!empty($horse_name)) {
    $sql .= " WHERE 一着馬名 LIKE '%" . $conn->real_escape_string($horse_name) . "%'";
}

// SQLクエリをデバッグ用にログファイルに保存
logError("実行するSQL: " . $sql);

// クエリを実行
$result = $conn->query($sql);

// クエリの結果をチェック
if (!$result) {
    $errorMessage = "クエリ実行失敗: " . $conn->error;
    logError($errorMessage);  // エラーをログファイルに保存
    die($errorMessage);  // エラーメッセージを画面にも表示
}

$data = array();

// データベースから取得した結果を処理
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $data[] = array(
            'date' => $row['日付'],
            'keibajo' => $row['競馬場'],
            'race_name' => $row['レース名'],
            'position' => $row['着順'],
            'surface' => $row['芝ダート'],
            'distance' => $row['距離'],
            'winner' => $row['一着馬名'],
            'odds' => $row['一着単勝配当']
        );
    }
} else {
    // データが見つからなかった場合のデバッグメッセージ
    logError("データが見つかりませんでした: " . $sql);
    echo json_encode(array('message' => 'データが見つかりませんでした。'));
}

// JSON形式でデータを返す
echo json_encode($data);

// 接続を閉じる
$conn->close();
?>
