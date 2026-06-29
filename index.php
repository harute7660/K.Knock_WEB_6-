<?php
session_start();

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

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function s($v) {
    global $conn;
    return $conn->real_escape_string($v);
}

function user_id() {
    return $_SESSION['user_id'] ?? 0;
}

function username() {
    return $_SESSION['username'] ?? '';
}

function login_check() {
    if (!user_id()) {
        header('Location: /board/login');
        exit;
    }
}

function save_file($post_id) {
    global $conn;

    $original_name = $_FILES['upload_file']['name'];
    $tmp_name = $_FILES['upload_file']['tmp_name'];
    $size = $_FILES['upload_file']['size'];

    if ($original_name == '') {
        return;
    }

    $save_name = time() . '_' . $original_name;
    $stored_path = 'uploads/' . $save_name;

    move_uploaded_file($tmp_name, __DIR__ . '/' . $stored_path);

    $original_name = s($original_name);
    $stored_path = s($stored_path);

    $conn->query("
        INSERT INTO attachments (post_id, original_name, stored_path, size_bytes)
        VALUES ($post_id, '$original_name', '$stored_path', $size)
    ");
}

/*
    GET /
*/
if ($method == 'GET' && $path == '/') {
    $boards = $conn->query("
        SELECT *
        FROM boards
        ORDER BY id ASC
    ");
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <title>게시판</title>
    <style>
        h1 { color: blue; }
    </style>
</head>
<body>

<h1>게시판</h1>

<?php if (user_id()): ?>
    <p>
        로그인 사용자: <?= e(username()) ?>
        / <a href="/board/logout">로그아웃</a>
        / <a href="/board/users">유저 검색</a>
    </p>
<?php else: ?>
    <p>
        <a href="/board/signup">회원가입</a>
        / <a href="/board/login">로그인</a>
        / <a href="/board/users">유저 검색</a>
    </p>
<?php endif; ?>

<h2>게시판 선택</h2>

<ul>
    <?php while ($board = $boards->fetch_assoc()): ?>
        <li>
            <a href="/board/posts?board_id=<?= $board['id'] ?>">
                <?= e($board['name']) ?>
            </a>
        </li>
    <?php endwhile; ?>
</ul>

</body>
</html>
<?php
    exit;
}

/*
    GET /signup
*/
if ($method == 'GET' && $path == '/signup') {
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <title>회원가입</title>
    <style>
        h1 { color: blue; }
    </style>
</head>
<body>

<h1>회원가입</h1>

<form method="post" action="/board/signup">
    <p>
        아이디<br>
        <input type="text" name="username">
    </p>

    <p>
        비밀번호<br>
        <input type="password" name="password">
    </p>

    <button type="submit">회원가입</button>
</form>

<p><a href="/board/login">로그인</a></p>

</body>
</html>
<?php
    exit;
}

/*
    POST /signup
*/
if ($method == 'POST' && $path == '/signup') {
    $username = s($_POST['username']);
    $password = s($_POST['password']);

    $conn->query("
        INSERT INTO users (username, password)
        VALUES ('$username', '$password')
    ");

    header('Location: /board/login');
    exit;
}

/*
    GET /login
*/
if ($method == 'GET' && $path == '/login') {
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <title>로그인</title>
    <style>
        h1 { color: blue; }
    </style>
</head>
<body>

<h1>로그인</h1>

<form method="post" action="/board/login">
    <p>
        아이디<br>
        <input type="text" name="username">
    </p>

    <p>
        비밀번호<br>
        <input type="password" name="password">
    </p>

    <button type="submit">로그인</button>
</form>

<p><a href="/board/signup">회원가입</a></p>

</body>
</html>
<?php
    exit;
}

/*
    POST /login
*/
if ($method == 'POST' && $path == '/login') {
    $username = s($_POST['username']);
    $password = s($_POST['password']);

    $user = $conn->query("
        SELECT *
        FROM users
        WHERE username = '$username'
        AND password = '$password'
    ")->fetch_assoc();

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];

        header('Location: /board/');
        exit;
    }

    echo '로그인 실패';
    exit;
}

/*
    GET /logout
*/
if ($method == 'GET' && $path == '/logout') {
    session_destroy();

    header('Location: /board/login');
    exit;
}

/*
    GET /users
*/
if ($method == 'GET' && $path == '/users') {
    $keyword = $_GET['keyword'] ?? '';
    $keyword_sql = s($keyword);

    $users = $conn->query("
        SELECT id, username, created_at
        FROM users
        WHERE username LIKE '%$keyword_sql%'
        ORDER BY id DESC
    ");
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <title>유저 검색</title>
    <style>
        h1 { color: blue; }
    </style>
</head>
<body>

<p><a href="/board/">처음으로</a></p>

<h1>유저 검색</h1>

<form method="get" action="/board/users">
    <input type="text" name="keyword" value="<?= e($keyword) ?>">
    <button type="submit">검색</button>
</form>

<hr>

<?php while ($user = $users->fetch_assoc()): ?>
    <p>
        <?= $user['id'] ?>.
        <?= e($user['username']) ?>
        / 가입일: <?= $user['created_at'] ?>
    </p>
<?php endwhile; ?>

</body>
</html>
<?php
    exit;
}

/*
    GET /posts
*/
if ($method == 'GET' && $path == '/posts') {
    $board_id = $_GET['board_id'] ?? 1;
    $keyword = $_GET['keyword'] ?? '';
    $sort = $_GET['sort'] ?? 'latest';

    $keyword_sql = s($keyword);

    if ($sort == 'old') {
        $order = 'ASC';
    } else {
        $order = 'DESC';
    }

    $board = $conn->query("
        SELECT *
        FROM boards
        WHERE id = $board_id
    ")->fetch_assoc();

    $boards = $conn->query("
        SELECT *
        FROM boards
        ORDER BY id ASC
    ");

    $posts = $conn->query("
        SELECT p.id, p.title, p.content, p.created_at, p.updated_at, u.username
        FROM posts p
        JOIN users u ON u.id = p.author_id
        WHERE p.board_id = $board_id
        AND (p.title LIKE '%$keyword_sql%' OR p.content LIKE '%$keyword_sql%')
        ORDER BY p.id $order
    ");
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <title>게시글 목록</title>
    <style>
        h1 { color: blue; }
    </style>
</head>
<body>

<p>
    <a href="/board/">처음으로</a>
    / <a href="/board/users">유저 검색</a>

    <?php if (user_id()): ?>
        / <?= e(username()) ?>
        / <a href="/board/logout">로그아웃</a>
    <?php else: ?>
        / <a href="/board/login">로그인</a>
        / <a href="/board/signup">회원가입</a>
    <?php endif; ?>
</p>

<h1><?= e($board['name']) ?></h1>

<h2>게시판 이동</h2>

<ul>
    <?php while ($b = $boards->fetch_assoc()): ?>
        <li>
            <a href="/board/posts?board_id=<?= $b['id'] ?>">
                <?= e($b['name']) ?>
            </a>
        </li>
    <?php endwhile; ?>
</ul>

<hr>

<h2>게시물 검색 / 정렬</h2>

<form method="get" action="/board/posts">
    <input type="hidden" name="board_id" value="<?= $board_id ?>">

    <input type="text" name="keyword" value="<?= e($keyword) ?>">

    <select name="sort">
        <option value="latest" <?php if ($sort == 'latest') echo 'selected'; ?>>최신순</option>
        <option value="old" <?php if ($sort == 'old') echo 'selected'; ?>>오래된순</option>
    </select>

    <button type="submit">검색</button>
</form>

<hr>

<h2>게시글 작성</h2>

<?php if (user_id()): ?>
    <form method="post" action="/board/posts" enctype="multipart/form-data">
        <input type="hidden" name="board_id" value="<?= $board_id ?>">

        <p>
            제목<br>
            <input type="text" name="title">
        </p>

        <p>
            본문<br>
            <textarea name="content" rows="5"></textarea>
        </p>

        <p>
            파일 첨부<br>
            <input type="file" name="upload_file">
        </p>

        <button type="submit">작성</button>
    </form>
<?php else: ?>
    <p>글을 쓰려면 로그인해야 한다.</p>
<?php endif; ?>

<hr>

<h2>게시글 목록</h2>

<?php while ($post = $posts->fetch_assoc()): ?>
    <p>
        <a href="/board/posts/<?= $post['id'] ?>">
            <?= $post['id'] ?>. <?= e($post['title']) ?>
        </a>
        / 작성자: <?= e($post['username']) ?>
        / 작성일: <?= $post['created_at'] ?>
    </p>
<?php endwhile; ?>

</body>
</html>
<?php
    exit;
}

/*
    POST /posts
*/
if ($method == 'POST' && $path == '/posts') {
    login_check();

    $board_id = $_POST['board_id'];
    $title = s($_POST['title']);
    $content = s($_POST['content']);
    $author_id = user_id();

    $conn->query("
        INSERT INTO posts (board_id, title, content, author_id)
        VALUES ($board_id, '$title', '$content', $author_id)
    ");

    $post_id = $conn->insert_id;

    if (!empty($_FILES['upload_file']['name'])) {
        save_file($post_id);
    }

    header('Location: /board/posts/' . $post_id);
    exit;
}
/*
    GET /posts/:id
*/
if ($method == 'GET' && preg_match('#^/posts/([0-9]+)$#', $path, $m)) {
    $id = $m[1];

    $post = $conn->query("
        SELECT p.*, u.username, b.name AS board_name
        FROM posts p
        JOIN users u ON u.id = p.author_id
        JOIN boards b ON b.id = p.board_id
        WHERE p.id = $id
    ")->fetch_assoc();

    $comments = $conn->query("
        SELECT c.*, u.username
        FROM comments c
        JOIN users u ON u.id = c.author_id
        WHERE c.post_id = $id
        ORDER BY c.id ASC
    ");

    $files = $conn->query("
        SELECT *
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
        h1 { color: blue; }
    </style>
</head>
<body>

<p>
    <a href="/board/posts?board_id=<?= $post['board_id'] ?>">게시글 목록</a>

    <?php if (user_id()): ?>
        / <?= e(username()) ?>
        / <a href="/board/logout">로그아웃</a>
    <?php else: ?>
        / <a href="/board/login">로그인</a>
    <?php endif; ?>
</p>

<h1>게시글 상세</h1>

<p>게시판: <?= e($post['board_name']) ?></p>

<h2><?= e($post['title']) ?></h2>

<p>
    작성자: <?= e($post['username']) ?>
    / 작성일: <?= $post['created_at'] ?>
    / 수정일: <?= $post['updated_at'] ?>
</p>

<p><?= nl2br(e($post['content'])) ?></p>

<hr>

<?php if (user_id() == $post['author_id']): ?>
    <h2>게시글 수정</h2>

    <form method="post" action="/board/posts/<?= $post['id'] ?>" enctype="multipart/form-data">
        <input type="hidden" name="_method" value="PUT">

        <p>
            제목<br>
            <input type="text" name="title" value="<?= e($post['title']) ?>">
        </p>

        <p>
            본문<br>
            <textarea name="content" rows="5"><?= e($post['content']) ?></textarea>
        </p>

        <p>
            파일 수정<br>
            <input type="file" name="upload_file">
        </p>

        <button type="submit">수정</button>
    </form>

    <h2>게시글 삭제</h2>

    <form method="post" action="/board/posts/<?= $post['id'] ?>">
        <input type="hidden" name="_method" value="DELETE">
        <button type="submit">삭제</button>
    </form>
<?php endif; ?>

<hr>

<h1>댓글</h1>

<?php if (user_id()): ?>
    <h2>댓글 작성</h2>

    <form method="post" action="/board/posts/<?= $post['id'] ?>/comments">
        <p>
            댓글<br>
            <textarea name="content" rows="3"></textarea>
        </p>

        <button type="submit">댓글 작성</button>
    </form>
<?php else: ?>
    <p>댓글을 쓰려면 로그인해야 한다.</p>
<?php endif; ?>

<h2>댓글 목록</h2>

<?php while ($comment = $comments->fetch_assoc()): ?>
    <hr>

    <p>
        작성자: <?= e($comment['username']) ?>
        / 작성일: <?= $comment['created_at'] ?>
    </p>

    <p><?= nl2br(e($comment['content'])) ?></p>

    <?php if (user_id() == $comment['author_id']): ?>
        <form method="post" action="/board/comments/<?= $comment['id'] ?>">
            <input type="hidden" name="_method" value="PUT">
            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">

            <textarea name="content" rows="3"><?= e($comment['content']) ?></textarea>
            <br>

            <button type="submit">댓글 수정</button>
        </form>

        <form method="post" action="/board/comments/<?= $comment['id'] ?>">
            <input type="hidden" name="_method" value="DELETE">
            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
            <button type="submit">댓글 삭제</button>
        </form>
    <?php endif; ?>
<?php endwhile; ?>

<hr>

<h1>파일</h1>

<?php if (user_id()): ?>
    <h2>파일 업로드</h2>

    <form method="post" action="/board/posts/<?= $post['id'] ?>/files" enctype="multipart/form-data">
        <input type="file" name="upload_file">
        <button type="submit">업로드</button>
    </form>
<?php endif; ?>

<h2>파일 목록</h2>

<ul>
    <?php while ($file = $files->fetch_assoc()): ?>
        <li>
            <a href="/board/files/<?= $file['id'] ?>">
                <?= e($file['original_name']) ?>
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
    PUT /posts/:id
*/
if ($method == 'PUT' && preg_match('#^/posts/([0-9]+)$#', $path, $m)) {
    login_check();

    $id = $m[1];

    $post = $conn->query("
        SELECT *
        FROM posts
        WHERE id = $id
    ")->fetch_assoc();

    if (user_id() != $post['author_id']) {
        echo '작성자만 수정 가능';
        exit;
    }

    $title = s($_POST['title']);
    $content = s($_POST['content']);

    $conn->query("
        UPDATE posts
        SET title = '$title', content = '$content'
        WHERE id = $id
    ");

    if (isset($_FILES['upload_file']) && $_FILES['upload_file']['name'] != '') {
        $old_files = $conn->query("
            SELECT stored_path
            FROM attachments
            WHERE post_id = $id
        ");

        while ($file = $old_files->fetch_assoc()) {
            $file_path = __DIR__ . '/' . $file['stored_path'];

            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }

        $conn->query("
            DELETE FROM attachments
            WHERE post_id = $id
        ");

        save_file($id);
    }

    header('Location: /board/posts/' . $id);
    exit;
}

/*
    DELETE /posts/:id
*/
if ($method == 'DELETE' && preg_match('#^/posts/([0-9]+)$#', $path, $m)) {
    login_check();

    $id = $m[1];

    $post = $conn->query("
        SELECT *
        FROM posts
        WHERE id = $id
    ")->fetch_assoc();

    if (user_id() != $post['author_id']) {
        echo '작성자만 삭제 가능';
        exit;
    }

    $files = $conn->query("
        SELECT stored_path
        FROM attachments
        WHERE post_id = $id
    ");

    while ($file = $files->fetch_assoc()) {
        $file_path = __DIR__ . '/' . $file['stored_path'];

        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    $board_id = $post['board_id'];

    $conn->query("DELETE FROM comments WHERE post_id = $id");
    $conn->query("DELETE FROM attachments WHERE post_id = $id");
    $conn->query("DELETE FROM posts WHERE id = $id");

    header('Location: /board/posts?board_id=' . $board_id);
    exit;
}

/*
    POST /posts/:id/comments
*/
if ($method == 'POST' && preg_match('#^/posts/([0-9]+)/comments$#', $path, $m)) {
    login_check();

    $post_id = $m[1];
    $content = s($_POST['content']);
    $author_id = user_id();

    $conn->query("
        INSERT INTO comments (post_id, author_id, content)
        VALUES ($post_id, $author_id, '$content')
    ");

    header('Location: /board/posts/' . $post_id);
    exit;
}

/*
    PUT /comments/:id
*/
if ($method == 'PUT' && preg_match('#^/comments/([0-9]+)$#', $path, $m)) {
    login_check();

    $id = $m[1];
    $post_id = $_POST['post_id'];

    $comment = $conn->query("
        SELECT *
        FROM comments
        WHERE id = $id
    ")->fetch_assoc();

    if (user_id() != $comment['author_id']) {
        echo '작성자만 수정 가능';
        exit;
    }

    $content = s($_POST['content']);

    $conn->query("
        UPDATE comments
        SET content = '$content'
        WHERE id = $id
    ");

    header('Location: /board/posts/' . $post_id);
    exit;
}

/*
    DELETE /comments/:id
*/
if ($method == 'DELETE' && preg_match('#^/comments/([0-9]+)$#', $path, $m)) {
    login_check();

    $id = $m[1];
    $post_id = $_POST['post_id'];

    $comment = $conn->query("
        SELECT *
        FROM comments
        WHERE id = $id
    ")->fetch_assoc();

    if (user_id() != $comment['author_id']) {
        echo '작성자만 삭제 가능';
        exit;
    }

    $conn->query("
        DELETE FROM comments
        WHERE id = $id
    ");

    header('Location: /board/posts/' . $post_id);
    exit;
}

/*
    POST /posts/:id/files
*/
if ($method == 'POST' && preg_match('#^/posts/([0-9]+)/files$#', $path, $m)) {
    login_check();

    $post_id = $m[1];

    save_file($post_id);

    header('Location: /board/posts/' . $post_id);
    exit;
}

/*
    GET /files/:id
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
