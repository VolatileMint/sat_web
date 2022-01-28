<?// DBに接続
$dbh = new PDO('mysql:host=mysql;dbname=techc', 'root', '');
session_start();
if (empty($_SESSION['login_user_id'])) { // 非ログインの場合利用不可
  header("HTTP/1.1 302 Found");
  header("Location: /login.php");
  return;
}
$flag = $_GET['success'] ?? '';
$user = null;
// 現在のログイン情報を取得する
$user_select_sth = $dbh->prepare("SELECT * from users WHERE id = :id");
$user_select_sth->execute([':id' => $_SESSION['login_user_id']]);
$user = $user_select_sth->fetch();
// DBに接続
$dbh = new PDO('mysql:host=mysql;dbname=techc', 'root', '');
// セッションにあるログインIDから、ログインしている対象の会員情報を引く
$select_sth = $dbh->prepare("SELECT * FROM users WHERE id = :id");
$select_sth->execute([
    ':id' => $_SESSION['login_user_id'],
]);
$user = $select_sth->fetch();

if (isset($_POST['image_base64'])) {
  // POSTで送られてくるフォームパラメータ image_base64 がある場合
  $image_filename = null;
  if (!empty($_POST['image_base64'])) {
    // 先頭の data:~base64, のところは削る
    $base64 = preg_replace('/^data:.+base64,/', '', $_POST['image_base64']);
    // base64からバイナリにデコードする
    $image_binary = base64_decode($base64);
    // 新しいファイル名を決めてバイナリを出力する
    $image_filename = strval(time()) . bin2hex(random_bytes(25)) . '.png';
    $filepath =  '/var/www/public/image/' . $image_filename;
    file_put_contents($filepath, $image_binary);
  }

  // ログインしている会員情報のnameカラムを更新する
  $update_sth = $dbh->prepare("UPDATE users SET icon_filename = :icon_filename WHERE id = :id");
  $update_sth->execute([
      ':id' => $user['id'],
      ':icon_filename' => $image_filename,
  ]);
  // 成功したら成功したことを示すクエリパラメータつきのURLにリダイレクト
  header("HTTP/1.1 302 Found");
  header("Location: ./icon.php?success=1");
  return;
}

?>
<a href="/timeline.php">タイムラインに戻る</a>
<?php if($flag === '1'):?>
	<p>変更が完了しました</p>
<?php endif;?>
<h1>アイコンの変更</h1>
<div class="profile" style="width: 100%; height:15em;  padding:1em;
    <?php if(!empty($user['cover_filename'])): ?>
		background-image: url('/image/<?= nl2br(htmlspecialchars($user['cover_filename'])) ?>'); 
		background-size:cover;
    <?php endif; ?>">
	<div class="line" style="overflow: hidden; margin-bottom: 10px;">
		<div class="icon">
		  <?php if(empty($user['icon_filename'])): ?>
			<div class="perfect-circle" style="width: 5em;
				height: 5em;
				border-radius: 50%;
				background: gray;
				object-fit: cover;
				border: 3px solid white; float: left; margin-right:1em;">
			</div>
		  <?php else: ?>
		  <img src="/image/<?= nl2br(htmlspecialchars($user['icon_filename'])) ?>" id="icon_img" 
			style="height: 5em; width: 5em; border-radius: 50%; object-fit: cover;border: 3px solid white; float: left; margin-right:1em;">
		  <?php endif; ?>
		</div>
		<div class="name">
			<h1><?= htmlspecialchars($user['name']) ?></h1>
		</div>
	</div>
	<div class="textarea" style="background-color: rgba(255,255,255,0.8); width:40em; height: 8em; padding:5px 15px;">
		<?php if(!empty($user['birthday'])): ?>
			<?php
			  $birthday = DateTime::createFromFormat('Y-m-d', $user['birthday']);
			  $today = new DateTime('now');
			?>
			  <p><?= $today->diff($birthday)->y . "歳(" . date('Y年n月j日', strtotime($user['birthday'])) . "生まれ)"?></p>
		<?php endif; ?>
		<p><?= nl2br(htmlspecialchars($user['introduction'])) ?></p>
	</div>
</div>

<hr>
<form method="POST">
	<div style="margin: 1em 0;">
		<input type="file" accept="image/*" name="image" id="imageInput">
	</div>
	<input id="imageBase64Input" type="hidden" name="image_base64"><!-- base64を送る用のinput (非表示) -->
	<canvas id="imageCanvas" style="display: none;"></canvas><!-- 画像縮小に使うcanvas (非表示) -->
	<button type="submit">変更する</button>
</form>
<ul>
  <li><a href="./index.php">設定一覧に戻る</a></li>
  <li><a href="./icon.php">アイコン設定</a></li>
  <li><a href="./cover.php">カバー画像設定</a></li>
</ul>
<script>
document.addEventListener("DOMContentLoaded", () => {
  const imageInput = document.getElementById("imageInput");
  imageInput.addEventListener("change", () => {
    if (imageInput.files.length < 1) {
      // 未選択の場合
      return;
    }
	const icon_img = document.getElementById("icon_img");
    const file = imageInput.files[0];
    if (!file.type.startsWith('image/')){ // 画像でなければスキップ
      return;
    }

    // 画像縮小処理
    const imageBase64Input = document.getElementById("imageBase64Input"); // base64を送るようのinput
    const canvas = document.getElementById("imageCanvas"); // 描画するcanvas
    const reader = new FileReader();
    const image = new Image();
    reader.onload = () => { // ファイルの読み込み完了したら動く処理を指定
      image.onload = () => { // 画像として読み込み完了したら動く処理を指定

        // 元の縦横比を保ったまま縮小するサイズを決めてcanvasの縦横に指定する
        const originalWidth = image.naturalWidth; // 元画像の横幅
        const originalHeight = image.naturalHeight; // 元画像の高さ
        const maxLength = 1000; // 横幅も高さも1000以下に縮小するものとする
        if (originalWidth <= maxLength && originalHeight <= maxLength) { // どちらもmaxLength以下の場合そのまま
            canvas.width = originalWidth;
            canvas.height = originalHeight;
        } else if (originalWidth > originalHeight) { // 横長画像の場合
            canvas.width = maxLength;
            canvas.height = maxLength * originalHeight / originalWidth;
        } else { // 縦長画像の場合
            canvas.width = maxLength * originalWidth / originalHeight;
            canvas.height = maxLength;
        }

        // canvasに実際に画像を描画 (canvasはdisplay:noneで隠れているためわかりにくいが...)
        const context = canvas.getContext("2d");
        context.drawImage(image, 0, 0, canvas.width, canvas.height);

        // canvasの内容をbase64に変換しinputのvalueに設定
        imageBase64Input.value = canvas.toDataURL();
		icon_img.setAttribute('src', canvas.toDataURL());
      };
      image.src = reader.result;
    };
    reader.readAsDataURL(file);
  });
});
</script>