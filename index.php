<?php
$conn = new mysqli('localhost', 'board_user', 'boardpass', 'board');
$conn->set_charset('utf8mb4');

$base = '/board';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = substr($uri, strlen($base));
$path = str_replace('/index.php', '', $path);
$path = '/' . trim($path, '/');

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST' && isset($_POST['_method'])) {
    $method = strtoupper($_POST['_method']);
}

/*
    GET /posts 게시글 목록 조회기능
*/
if ($method == 'GET' && $path == '/posts') {
    $posts = $conn->query("
        SELECT p.id, p.title, p.content, p.created_at, p.updated_at, u.username
        FROM posts p
        JOIN users u ON u.id = p.author_id
        ORDER BY p.id DESC
    ");

    $posts_edit = $conn->query("
        SELECT p.id, p.title, p.content, p.created_at, p.updated_at, u.username
        FROM posts p
        JOIN users u ON u.id = p.author_id
        ORDER BY p.id DESC
    ");

    $posts_delete = $conn->query("
        SELECT p.id, p.title, p.created_at, u.username
        FROM posts p
        JOIN users u ON u.id = p.author_id
        ORDER BY p.id DESC
    ");

    $posts_comment = $conn->query("
        SELECT id, title
        FROM posts
        ORDER BY id DESC
    ");

    $comments_edit = $conn->query("
        SELECT c.id, c.post_id, c.content, c.created_at, p.title, u.username
        FROM comments c
        JOIN posts p ON p.id = c.post_id
        JOIN users u ON u.id = c.author_id
        ORDER BY c.id DESC
    ");

    $comments_delete = $conn->query("
        SELECT c.id, c.post_id, c.content, c.created_at, p.title, u.username
        FROM comments c
        JOIN posts p ON p.id = c.post_id
        JOIN users u ON u.id = c.author_id
        ORDER BY c.id DESC
    ");

    $posts_file = $conn->query("
        SELECT id, title
        FROM posts
        ORDER BY id DESC
    ");

    $files = $conn->query("
        SELECT a.id, a.original_name, a.size_bytes, p.title
        FROM attachments a
        JOIN posts p ON p.id = a.post_id
        ORDER BY a.id DESC
    ");
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <title>게시판</title>
    <style>
        h1 {
            color: blue;
        }
    </style>
</head>
<body>

<table width="900">
    <tr>
        <td width="50%" valign="top">
            <h1>게시글</h1>
            <ul>
                <li><a href="/board/posts#post-write">작성</a></li>
                <li><a href="/board/posts#post-edit">수정</a></li>
                <li><a href="/board/posts#post-delete">삭제</a></li>
            </ul>
        </td>

        <td width="50%" valign="top">
            <h1>댓글</h1>
            <ul>
                <li><a href="/board/posts#comment-write">작성</a></li>
                <li><a href="/board/posts#comment-edit">수정</a></li>
                <li><a href="/board/posts#comment-delete">삭제</a></li>
            </ul>
        </td>
    </tr>

    <tr>
        <td valign="top">
            <h1>파일</h1>
            <ul>
                <li><a href="/board/posts#file-upload">업로드</a></li>
                <li><a href="/board/posts#file-download">다운로드</a></li>
            </ul>
        </td>

        <td></td>
    </tr>
</table>

<hr>

<h1 id="post-write">게시글 작성</h1>

<form method="post" action="/board/posts">
    <p>
        제목<br>
        <input type="text" name="title">
    </p>

    <p>
        본문<br>
        <textarea name="content" rows="5"></textarea>
    </p>

    <button type="submit">작성</button>
</form>

<hr>

<h1>게시글 목록</h1>

<?php while ($post = $posts->fetch_assoc()): ?>
    <p>
        <a href="/board/posts/<?= $post['id'] ?>">
            <?= $post['id'] ?>. <?= $post['title'] ?>
        </a>
        / 작성자: <?= $post['username'] ?>
        / 작성일: <?= $post['created_at'] ?>
    </p>
<?php endwhile; ?>

<hr>

<h1 id="post-edit">게시글 수정</h1>

<?php while ($post = $posts_edit->fetch_assoc()): ?>
    <hr>

    <p>
        <?= $post['id'] ?>. <?= $post['title'] ?>
        / 작성자: <?= $post['username'] ?>
    </p>

    <form method="post" action="/board/posts/<?= $post['id'] ?>">
        <input type="hidden" name="_method" value="PUT">

        <p>
            제목<br>
            <input type="text" name="title" value="<?= $post['title'] ?>">
        </p>

        <p>
            본문<br>
            <textarea name="content" rows="5"><?= $post['content'] ?></textarea>
        </p>

        <button type="submit">수정</button>
    </form>
<?php endwhile; ?>

<hr>

<h1 id="post-delete">게시글 삭제</h1>

<?php while ($post = $posts_delete->fetch_assoc()): ?>
    <hr>

    <p>
        <?= $post['id'] ?>. <?= $post['title'] ?>
        / 작성자: <?= $post['username'] ?>
    </p>

    <form method="post" action="/board/posts/<?= $post['id'] ?>">
        <input type="hidden" name="_method" value="DELETE">
        <button type="submit">삭제</button>
    </form>
<?php endwhile; ?>

<hr>

<h1 id="comment-write">댓글 작성</h1>

<?php while ($post = $posts_comment->fetch_assoc()): ?>
    <hr>

    <p>
        <?= $post['id'] ?>. <?= $post['title'] ?>
    </p>

    <form method="post" action="/board/posts/<?= $post['id'] ?>/comments">
        <p>
            댓글<br>
            <textarea name="content" rows="3"></textarea>
        </p>

        <button type="submit">댓글 작성</button>
    </form>
<?php endwhile; ?>

<hr>

<h1 id="comment-edit">댓글 수정</h1>

<?php while ($comment = $comments_edit->fetch_assoc()): ?>
    <hr>

    <p>
        게시글: <?= $comment['title'] ?>
        / 작성자: <?= $comment['username'] ?>
        / 작성일: <?= $comment['created_at'] ?>
    </p>

    <form method="post" action="/board/comments/<?= $comment['id'] ?>">
        <input type="hidden" name="_method" value="PUT">
        <input type="hidden" name="post_id" value="<?= $comment['post_id'] ?>">

        <textarea name="content" rows="3"><?= $comment['content'] ?></textarea>
        <br>

        <button type="submit">댓글 수정</button>
    </form>
<?php endwhile; ?>

<hr>

<h1 id="comment-delete">댓글 삭제</h1>

<?php while ($comment = $comments_delete->fetch_assoc()): ?>
    <hr>

    <p>
        게시글: <?= $comment['title'] ?>
        / 댓글: <?= $comment['content'] ?>
        / 작성자: <?= $comment['username'] ?>
    </p>

    <form method="post" action="/board/comments/<?= $comment['id'] ?>">
        <input type="hidden" name="_method" value="DELETE">
        <input type="hidden" name="post_id" value="<?= $comment['post_id'] ?>">
        <button type="submit">댓글 삭제</button>
    </form>
<?php endwhile; ?>

<hr>

<h1 id="file-upload">파일 업로드</h1>

<?php while ($post = $posts_file->fetch_assoc()): ?>
    <hr>

    <p>
        <?= $post['id'] ?>. <?= $post['title'] ?>
    </p>

    <form method="post" action="/board/posts/<?= $post['id'] ?>/files" enctype="multipart/form-data">
        <input type="file" name="upload_file">
        <button type="submit">업로드</button>
    </form>
<?php endwhile; ?>

<hr>

<h1 id="file-download">파일 다운로드</h1>

<ul>
    <?php while ($file = $files->fetch_assoc()): ?>
        <li>
            게시글: <?= $file['title'] ?> /
            <a href="/board/files/<?= $file['id'] ?>">
                <?= $file['original_name'] ?>
            </a>
            (<?= $file['size_bytes'] ?> bytes)
        </li>
    <?php endwhile; ?>
</ul>

</body>
</html>
<?php
    exit;
}

/*
    POST /posts 게시글 작성 기능
*/
if ($method == 'POST' && $path == '/posts') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $author_id = 1;

    $conn->query("
        INSERT INTO posts (title, content, author_id)
        VALUES ('$title', '$content', $author_id)
    ");

    header('Location: /board/posts');
    exit;
}

/*
    GET /posts/:id 게시글 상세보기
*/
if ($method == 'GET' && preg_match('#^/posts/([0-9]+)$#', $path, $m)) {
    $id = $m[1];

    $post = $conn->query("
        SELECT p.id, p.title, p.content, p.created_at, p.updated_at, u.username
        FROM posts p
        JOIN users u ON u.id = p.author_id
        WHERE p.id = $id
    ")->fetch_assoc();

    $comments = $conn->query("
        SELECT c.id, c.content, c.created_at, u.username
        FROM comments c
        JOIN users u ON u.id = c.author_id
        WHERE c.post_id = $id
        ORDER BY c.id ASC
    ");

    $files = $conn->query("
        SELECT id, original_name, stored_path, size_bytes, created_at
        FROM attachments
        WHERE post_id = $id
        ORDER BY id DESC
    ");
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <title>게시글 상세</title>
    <style>
        h1 {
            color: blue;
        }
    </style>
</head>
<body>

<p><a href="/board/posts">게시글 목록</a></p>

<h1>게시글 상세</h1>

<h2><?= $post['title'] ?></h2>

<p>
    작성자: <?= $post['username'] ?>
    / 작성일: <?= $post['created_at'] ?>
    / 수정일: <?= $post['updated_at'] ?>
</p>

<p><?= nl2br($post['content']) ?></p>

<hr>

<h1>댓글</h1>

<?php while ($comment = $comments->fetch_assoc()): ?>
    <p>
        <?= $comment['username'] ?> :
        <?= nl2br($comment['content']) ?>
        / <?= $comment['created_at'] ?>
    </p>
<?php endwhile; ?>

<hr>

<h1>파일</h1>

<ul>
    <?php while ($file = $files->fetch_assoc()): ?>
        <li>
            <a href="/board/files/<?= $file['id'] ?>">
                <?= $file['original_name'] ?>
            </a>
            (<?= $file['size_bytes'] ?> bytes)
        </li>
    <?php endwhile; ?>
</ul>

</body>
</html>
<?php
    exit;
}

/*
    PUT /posts/:id  게시글 수정 기능
*/
if ($method == 'PUT' && preg_match('#^/posts/([0-9]+)$#', $path, $m)) {
    $id = $m[1];
    $title = $_POST['title'];
    $content = $_POST['content'];

    $conn->query("
        UPDATE posts
        SET title = '$title', content = '$content'
        WHERE id = $id
    ");

    header('Location: /board/posts');
    exit;
}

/*
    DELETE /posts/:id 게시글 삭제기능
*/
if ($method == 'DELETE' && preg_match('#^/posts/([0-9]+)$#', $path, $m)) {
    $id = $m[1];

    $files = $conn->query("
        SELECT stored_path
        FROM attachments
        WHERE post_id = $id
    ");

    while ($file = $files->fetch_assoc()) {
        $file_path = __DIR__ . '/' . $file['stored_path'];
        unlink($file_path);
    }

    $conn->query("DELETE FROM comments WHERE post_id = $id");
    $conn->query("DELETE FROM attachments WHERE post_id = $id");
    $conn->query("DELETE FROM posts WHERE id = $id");

    header('Location: /board/posts');
    exit;
}

/*
    POST /posts/:id/comments       댓글 작성
*/
if ($method == 'POST' && preg_match('#^/posts/([0-9]+)/comments$#', $path, $m)) {
    $post_id = $m[1];
    $content = $_POST['content'];
    $author_id = 1;

    $conn->query("
        INSERT INTO comments (post_id, author_id, content)
        VALUES ($post_id, $author_id, '$content')
    ");

    header('Location: /board/posts');
    exit;
}

/*
    PUT /comments/:id        댓글 수정
*/
if ($method == 'PUT' && preg_match('#^/comments/([0-9]+)$#', $path, $m)) {
    $id = $m[1];
    $post_id = $_POST['post_id'];
    $content = $_POST['content'];

    $conn->query("
        UPDATE comments
        SET content = '$content'
        WHERE id = $id
    ");

    header('Location: /board/posts');
    exit;
}

/*
    DELETE /comments/:id     댓글 삭제하는 기능
*/
if ($method == 'DELETE' && preg_match('#^/comments/([0-9]+)$#', $path, $m)) {
    $id = $m[1];
    $post_id = $_POST['post_id'];

    $conn->query("DELETE FROM comments WHERE id = $id");

    header('Location: /board/posts');
    exit;
}

/*
    POST /posts/:id/files            파일 업로드
*/
if ($method == 'POST' && preg_match('#^/posts/([0-9]+)/files$#', $path, $m)) {
    $post_id = $m[1];

    $original_name = $_FILES['upload_file']['name'];
    $tmp_name = $_FILES['upload_file']['tmp_name'];
    $size = $_FILES['upload_file']['size'];

    $save_name = time() . '_' . $original_name;
    $stored_path = 'uploads/' . $save_name;

    move_uploaded_file($tmp_name, __DIR__ . '/' . $stored_path);

    $conn->query("
        INSERT INTO attachments (post_id, original_name, stored_path, size_bytes)
        VALUES ($post_id, '$original_name', '$stored_path', $size)
    ");

    header('Location: /board/posts');
    exit;
}

/*
    GET /files/:id    파일 다운로드
*/
if ($method == 'GET' && preg_match('#^/files/([0-9]+)$#', $path, $m)) {
    $id = $m[1];

    $file = $conn->query("
        SELECT original_name, stored_path, size_bytes
        FROM attachments
        WHERE id = $id
    ")->fetch_assoc();

    $file_path = __DIR__ . '/' . $file['stored_path'];

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $file['original_name'] . '"');
    header('Content-Length: ' . filesize($file_path));

    readfile($file_path);
    exit;
}

echo '404';
