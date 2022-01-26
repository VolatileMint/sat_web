<?php
$dbh = new PDO('mysql:host=mysql;dbname=techc', 'root', '');
session_start();
if (empty($_SESSION['login_user_id'])) { // 非ログインの場合利用不可 401 で空のものを返す
  header("HTTP/1.1 401 Unauthorized");
  header("Content-Type: application/json");
  print(json_encode(['entries' => []]));
  return;
}

// WHERE句を変数として持っておく
$where = 'bbs_entries.user_id IN (SELECT followee_user_id FROM user_relationships WHERE follower_user_id = :login_user_id) OR bbs_entries.user_id = :login_user_id';
//バインドする値をハッシュ配列で持っておく
$bind　= [];
$bind[':login_user_id'] = $_SESSION['login_user_id'];

/*
$read_id = null;
// 最終読み込み番号があった場合、
if($_SERVER["REQUEST_METHOD"] === "GET"){
	// 一応のvaridate
	
	$read_id = $_GET["read"]) === "integer"){
		$read_id = $_GET["read"];
		$where .= ' AND bbs_entries.id < :read_id';
		$bind[':read_id'] = $read_id;
	}
}
*/

// 現在のログイン情報を取得する
$user_select_sth = $dbh->prepare("SELECT * from users WHERE id = :id");
$user_select_sth->execute([':id' => $_SESSION['login_user_id']]);
$user = $user_select_sth->fetch();
// 投稿データを取得。IN句の中身もプレースホルダを使うために、$target_user_ids の要素数だけ「?」を付けている。
$sql = 'SELECT bbs_entries.*, users.name AS user_name, users.icon_filename AS user_icon_filename'
  . ' FROM bbs_entries'
  . ' INNER JOIN users ON bbs_entries.user_id = users.id'
  . ' WHERE '
  . $where
  . ' ORDER BY bbs_entries.created_at DESC'
  . ' LIMIT 10';
$select_sth = $dbh->prepare($sql);
// バインド
$select_sth->execute($bind);
$list = $select_sth->fetchAll(\PDO::FETCH_ASSOC);
//
$sql = 'SELECT image_filename FROM bbs_images 
		WHERE id = :id';
$select_img = $dbh->prepare($sql);

foreach($list as $k => $v){
	$select_img->execute([':id' => $v['id'],]);
	$datum = $select_img->fetchAll(PDO::FETCH_ASSOC); //PDO::FETCH_ASSOC(重複表示を省く);
	if(false === $datum){
		return null;
	}
	$image = array_column($datum, 'image_filename');
	$data = [];
	foreach($image as $i){
		$data[] = '/image/' . $i;
	}
	$v['images'] = $data;
	$list[$k] = $v;
}
// bodyのHTMLを出力するための関数を用意する
function bodyFilter (string $body): string
{
  $body = htmlspecialchars($body); // エスケープ処理
  $body = nl2br($body); // 改行文字を<br>要素に変換
  // >>1 といった文字列を該当番号の投稿へのページ内リンクとする (レスアンカー機能)
  // 「>」(半角の大なり記号)は htmlspecialchars() でエスケープされているため注意
  $body = preg_replace('/&gt;&gt;(\d+)/', '<a href="#entry$1">&gt;&gt;$1</a>', $body);
  return $body;
}
// JSONに吐き出す用のentries
$result_entries = [];
foreach ($list as $entry) {
  $result_entry = [
    'id' => $entry['id'],
    'user_name' => $entry['user_name'],
    'user_icon_file_url' => empty($entry['user_icon_filename']) ? '' : ('/image/' . $entry['user_icon_filename']),
    'user_profile_url' => '/profile.php?user_id=' . $entry['user_id'],
    'body' => bodyFilter($entry['body']),
    'image_file_url' => $entry['images'],
    'created_at' => $entry['created_at'],
  ];
  $result_entries[] = $result_entry;
}
header("HTTP/1.1 200 OK");
header("Content-Type: application/json");
print(json_encode(['entries' => $result_entries]));