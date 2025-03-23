<?php
// Kích hoạt hiển thị lỗi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Định nghĩa đường dẫn gốc
define('ROOT_DIR', dirname(__DIR__));

// Bắt đầu session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo "<h1>Debug Đăng nhập</h1>";

// Xử lý tạo tài khoản mới
if (isset($_POST['create_account'])) {
    try {
        // Lấy dữ liệu từ form
        $new_username = isset($_POST['new_username']) ? trim($_POST['new_username']) : '';
        $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
        $new_role = isset($_POST['new_role']) ? $_POST['new_role'] : 'student';
        
        // Kiểm tra dữ liệu đầu vào
        $errors = [];
        
        if (empty($new_username)) {
            $errors[] = "Username không được để trống";
        }
        
        if (empty($new_password)) {
            $errors[] = "Password không được để trống";
        }
        
        if (empty($new_role)) {
            $errors[] = "Role không được để trống";
        }
        
        // Kết nối CSDL
        if (file_exists(ROOT_DIR . '/config/db.php')) {
            require_once ROOT_DIR . '/config/db.php';
            
            if (isset($conn) && !$conn->connect_error) {
                // Kiểm tra username đã tồn tại chưa
                $check_stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
                $check_stmt->bind_param("s", $new_username);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    $errors[] = "Username đã tồn tại trong hệ thống";
                }
                
                // Nếu không có lỗi, thêm người dùng mới
                if (empty($errors)) {
                    // Hash mật khẩu
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    // Chuẩn bị câu lệnh INSERT - FIX HERE
                    $stmt = $conn->prepare("INSERT INTO users (username, password, role, is_first_login) VALUES (?, ?, ?, ?)");
                    
                    $is_first_login = 0; // Đặt thành 0 để người dùng không phải đổi mật khẩu ngay
                    $stmt->bind_param("sssi", $new_username, $hashed_password, $new_role, $is_first_login);
                    
                    if ($stmt->execute()) {
                        echo "<div style='background-color: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 20px;'>";
                        echo "<h3>Tạo tài khoản thành công!</h3>";
                        echo "<p>Username: <strong>$new_username</strong></p>";
                        echo "<p>Password: <strong>$new_password</strong></p>";
                        echo "<p>Role: <strong>$new_role</strong></p>";
                        echo "</div>";
                    } else {
                        echo "<div style='background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 20px;'>";
                        echo "<h3>Lỗi khi tạo tài khoản</h3>";
                        echo "<p>Lỗi: " . $stmt->error . "</p>";
                        echo "</div>";
                    }
                } else {
                    echo "<div style='background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 20px;'>";
                    echo "<h3>Lỗi khi tạo tài khoản</h3>";
                    echo "<ul>";
                    foreach ($errors as $error) {
                        echo "<li>$error</li>";
                    }
                    echo "</ul>";
                    echo "</div>";
                }
            } else {
                echo "<div style='background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 20px;'>";
                echo "<h3>Lỗi kết nối cơ sở dữ liệu</h3>";
                echo "<p>" . ($conn ? $conn->connect_error : "Không có kết nối") . "</p>";
                echo "</div>";
            }
        } else {
            echo "<div style='background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 20px;'>";
            echo "<h3>Lỗi</h3>";
            echo "<p>Không tìm thấy file cấu hình cơ sở dữ liệu</p>";
            echo "</div>";
        }
    } catch (Exception $e) {
        echo "<div style='background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 20px;'>";
        echo "<h3>Lỗi ngoại lệ</h3>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "</div>";
    }
}

// Log các thông tin POST để kiểm tra
if (!empty($_POST)) {
    echo "<h2>Dữ liệu POST</h2>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
}

// Phần code đăng nhập đã có (giữ nguyên)
if (isset($_POST['login'])) {
    // Lấy username và password từ form
    $login_username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    echo "<h2>Đang kiểm tra thông tin đăng nhập</h2>";
    echo "<p>Username: <strong>" . htmlspecialchars($login_username) . "</strong></p>";
    
    // Kiểm tra kết nối cơ sở dữ liệu
    try {
        if (file_exists(ROOT_DIR . '/config/db.php')) {
            require_once ROOT_DIR . '/config/db.php';
            
            if (isset($conn)) {
                if ($conn->connect_error) {
                    echo "<p style='color: red;'>Lỗi kết nối CSDL: " . $conn->connect_error . "</p>";
                } else {
                    echo "<p style='color: green;'>Kết nối CSDL thành công!</p>";
                    
                    // Hiển thị thông tin kết nối DB
                    echo "<h3>Thông tin kết nối CSDL</h3>";
                    echo "<ul>";
                    echo "<li>Host: " . $host . "</li>";
                    echo "<li>DB Username: " . $username . " (từ config)</li>";
                    echo "<li>Login Username: " . $_POST['username'] . " (từ form)</li>";
                    echo "<li>Database: " . $database . "</li>";
                    echo "</ul>";
                    
                    // Tìm người dùng theo username
                    $query = "SELECT * FROM users WHERE username = ?";
                    $stmt = $conn->prepare($query);
                    if (!$stmt) {
                        echo "<p style='color: red;'>Lỗi chuẩn bị truy vấn: " . $conn->error . "</p>";
                    } else {
                        $stmt->bind_param("s", $login_username);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows === 0) {
                            echo "<p style='color: red;'>Không tìm thấy người dùng với username: " . htmlspecialchars($login_username) . "</p>";
                            
                            // Hiển thị tất cả người dùng để kiểm tra
                            echo "<h3>Kiểm tra cấu trúc bảng users:</h3>";
                            $columnsResult = $conn->query("DESCRIBE users");
                            if ($columnsResult) {
                                echo "<table border='1'>";
                                echo "<tr><th>Field</th><th>Type</th><th>Key</th></tr>";
                                while ($column = $columnsResult->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . $column['Field'] . "</td>";
                                    echo "<td>" . $column['Type'] . "</td>";
                                    echo "<td>" . $column['Key'] . "</td>";
                                    echo "</tr>";
                                }
                                echo "</table>";
                            }
                            
                            // Hiển thị tất cả người dùng để kiểm tra
                            $allUsers = $conn->query("SELECT * FROM users");
                            if ($allUsers->num_rows > 0) {
                                echo "<h3>Danh sách người dùng trong hệ thống:</h3>";
                                echo "<table border='1'>";
                                
                                // Lấy tên các cột
                                $firstUser = $allUsers->fetch_assoc();
                                $allUsers->data_seek(0);
                                
                                // Tạo header cho bảng
                                echo "<tr>";
                                foreach (array_keys($firstUser) as $key) {
                                    echo "<th>$key</th>";
                                }
                                echo "</tr>";
                                
                                // Hiển thị dữ liệu người dùng
                                while ($user = $allUsers->fetch_assoc()) {
                                    echo "<tr>";
                                    foreach ($user as $key => $value) {
                                        // Hiển thị ngắn gọn nếu là mật khẩu
                                        echo "<td>";
                                        if ($key == 'password') {
                                            echo substr($value, 0, 10) . "...";
                                        } else {
                                            echo $value;
                                        }
                                        echo "</td>";
                                    }
                                    echo "</tr>";
                                }
                                echo "</table>";
                            } else {
                                echo "<p>Không có người dùng nào trong hệ thống.</p>";
                            }
                        } else {
                            echo "<p style='color: green;'>Đã tìm thấy người dùng!</p>";
                            
                            // Lấy thông tin người dùng
                            $user = $result->fetch_assoc();
                            
                            // Debug toàn bộ thông tin user
                            echo "<h3>Toàn bộ thông tin người dùng:</h3>";
                            echo "<pre>";
                            print_r($user);
                            echo "</pre>";
                            
                            // Hiển thị thông tin người dùng
                            echo "<h3>Thông tin người dùng</h3>";
                            echo "<ul>";
                            
                            // Kiểm tra xem trường ID là gì (user_id hay id)
                            $id_field = isset($user['user_id']) ? 'user_id' : (isset($user['id']) ? 'id' : null);
                            
                            if ($id_field) {
                                echo "<li>ID (" . $id_field . "): " . $user[$id_field] . "</li>";
                            } else {
                                echo "<li>ID: Không tìm thấy trường ID</li>";
                            }
                            
                            echo "<li>Username: " . $user['username'] . "</li>";
                            echo "<li>Role: " . $user['role'] . "</li>";
                            echo "<li>Password Hash (20 ký tự đầu): " . substr($user['password'], 0, 20) . "...</li>";
                            echo "</ul>";
                            
                            // Kiểm tra mật khẩu
                            $validPassword = password_verify($password, $user['password']);
                            
                            // Nếu password_verify thất bại, thử kiểm tra trực tiếp (cho trường hợp không hash)
                            $directMatch = ($password === $user['password']);
                            
                            if ($validPassword) {
                                echo "<p style='color: green;'>Mật khẩu chính xác! (Đã được hash)</p>";
                                
                                // Set session variables đúng trường ID
                                if ($id_field) {
                                    $_SESSION['user_id'] = $user[$id_field];
                                } else {
                                    // Tạo ID tạm thời nếu không tìm thấy
                                    $_SESSION['user_id'] = mt_rand(1000, 9999);
                                }
                                
                                $_SESSION['username'] = $user['username'];
                                $_SESSION['role'] = $user['role'];
                                $_SESSION['logged_in'] = true;
                                
                                echo "<p>Đã đặt biến session. Bạn đã đăng nhập thành công!</p>";
                                echo "<p><a href='/183intership'>Quay lại trang chủ</a></p>";
                            } else if ($directMatch) {
                                echo "<p style='color: orange;'>Mật khẩu khớp trực tiếp (không phải hash)!</p>";
                                
                                // Cập nhật mật khẩu thành hash
                                $newHash = password_hash($password, PASSWORD_DEFAULT);
                                $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
                                $updateStmt->bind_param("ss", $newHash, $login_username);
                                
                                if ($updateStmt->execute()) {
                                    echo "<p style='color: green;'>Đã cập nhật mật khẩu thành hash!</p>";
                                } else {
                                    echo "<p style='color: red;'>Lỗi cập nhật mật khẩu: " . $updateStmt->error . "</p>";
                                }
                                
                                // Set session variables đúng trường ID
                                if ($id_field) {
                                    $_SESSION['user_id'] = $user[$id_field];
                                } else {
                                    // Tạo ID tạm thời nếu không tìm thấy
                                    $_SESSION['user_id'] = mt_rand(1000, 9999);
                                }
                                
                                $_SESSION['username'] = $user['username'];
                                $_SESSION['role'] = $user['role'];
                                $_SESSION['logged_in'] = true;
                                
                                echo "<p>Đã đặt biến session. Bạn đã đăng nhập thành công!</p>";
                                echo "<p><a href='/183intership'>Quay lại trang chủ</a></p>";
                            } else {
                                echo "<p style='color: red;'>Mật khẩu không chính xác!</p>";
                                
                                // Debug hàm password_verify
                                echo "<h3>Debug password_verify</h3>";
                                echo "<pre>";
                                echo "Chuỗi mật khẩu nhập vào: " . $password . "\n";
                                echo "Password hash lưu trong CSDL: " . $user['password'] . "\n";
                                
                                // Tạo hash mới từ mật khẩu nhập vào để so sánh
                                $newHash = password_hash($password, PASSWORD_DEFAULT);
                                echo "Hash mới tạo từ mật khẩu nhập vào: " . $newHash . "\n";
                                
                                // Thông tin về thuật toán hash
                                $hashInfo = password_get_info($user['password']);
                                echo "Thông tin hash:\n";
                                print_r($hashInfo);
                                
                                // Kiểm tra nếu mật khẩu không phải là hash
                                if ($hashInfo['algo'] === 0) {
                                    echo "\nMật khẩu trong CSDL không phải là hash hợp lệ!\n";
                                    echo "Bạn có muốn cập nhật mật khẩu cho người dùng này không?\n";
                                    echo "</pre>";
                                    
                                    // Hiển thị form cập nhật mật khẩu
                                    echo "<form method='post' action=''>";
                                    echo "<input type='hidden' name='update_user' value='" . $user['username'] . "'>";
                                    echo "<input type='hidden' name='default_password' value='" . $user['username'] . "123'>";
                                    echo "<div>";
                                    echo "<label for='new_password'>Mật khẩu mới:</label>";
                                    echo "<input type='text' id='new_password' name='new_password' value='" . $user['username'] . "123'>";
                                    echo "</div><br>";
                                    echo "<button type='submit'>Cập nhật mật khẩu</button>";
                                    echo "</form>";
                                }
                                echo "</pre>";
                            }
                        }
                    }
                }
            } else {
                echo "<p style='color: red;'>Biến kết nối CSDL không tồn tại!</p>";
            }
        } else {
            echo "<p style='color: red;'>Không tìm thấy file cấu hình CSDL!</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Lỗi: " . $e->getMessage() . "</p>";
    }
}

?>

<hr>
<h2>Form Đăng nhập Debug</h2>
<form method="post">
    <div>
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>
    </div>
    <br>
    <div>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
    </div>
    <br>
    <button type="submit" name="login">Debug Đăng nhập</button>
</form>

<hr>
<h2>Tạo tài khoản mới</h2>
<form method="post">
    <div>
        <label for="new_username">Username:</label>
        <input type="text" id="new_username" name="new_username" required>
    </div>
    <br>
    <div>
        <label for="new_password">Password:</label>
        <input type="password" id="new_password" name="new_password" required>
    </div>
    <br>
    <div>
        <label for="new_role">Role:</label>
        <select id="new_role" name="new_role" required>
            <option value="admin">Admin</option>
            <option value="lecturer">Lecturer</option>
            <option value="student" selected>Student</option>
        </select>
    </div>
    <br>
    <button type="submit" name="create_account">Tạo tài khoản</button>
</form>

<hr>
<h2>Kiểm tra Session hiện tại</h2>
<pre>
<?php print_r($_SESSION); ?>
</pre>

<hr>
<h2>Công cụ quản lý tài khoản</h2>
<ul>
    <li><a href="/183intership/public/debug-login.php?action=list_users">Liệt kê tất cả người dùng</a></li>
    <li><a href="/183intership/public/debug-login.php?action=update_passwords">Cập nhật tất cả mật khẩu thành hash</a></li>
</ul>

<?php
// Xử lý các hành động
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    // Kết nối CSDL
    if (file_exists(ROOT_DIR . '/config/db.php')) {
        require_once ROOT_DIR . '/config/db.php';
        
        if (isset($conn) && !$conn->connect_error) {
            switch ($action) {
                case 'list_users':
                    // Hiển thị danh sách người dùng
                    $users_result = $conn->query("SELECT * FROM users");
                    
                    if ($users_result && $users_result->num_rows > 0) {
                        echo "<h3>Danh sách người dùng trong hệ thống:</h3>";
                        echo "<table border='1'>";
                        
                        // Lấy tên các cột
                        $first_user = $users_result->fetch_assoc();
                        $users_result->data_seek(0);
                        
                        // Tạo header cho bảng
                        echo "<tr>";
                        foreach (array_keys($first_user) as $key) {
                            echo "<th>$key</th>";
                        }
                        echo "<th>Actions</th>";
                        echo "</tr>";
                        
                        // Hiển thị dữ liệu người dùng
                        while ($user = $users_result->fetch_assoc()) {
                            echo "<tr>";
                            foreach ($user as $key => $value) {
                                echo "<td>";
                                if ($key == 'password') {
                                    echo substr($value, 0, 10) . "...";
                                } else {
                                    echo $value;
                                }
                                echo "</td>";
                            }
                            
                            // Thêm nút xóa
                            echo "<td>";
                            echo "<a href='/183intership/public/debug-login.php?action=delete_user&username=" . $user['username'] . "' onclick='return confirm(\"Bạn có chắc muốn xóa người dùng này?\")'>Xóa</a>";
                            echo "</td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                    } else {
                        echo "<p>Không có người dùng nào trong hệ thống.</p>";
                    }
                    break;
                
                case 'update_passwords':
                    // Cập nhật tất cả mật khẩu thành hash
                    $users_result = $conn->query("SELECT username, password FROM users");
                    $updated_count = 0;
                    $already_hashed = 0;
                    
                    if ($users_result) {
                        while ($user = $users_result->fetch_assoc()) {
                            $username = $user['username'];
                            $current_password = $user['password'];
                            
                            // Kiểm tra xem mật khẩu đã được hash chưa
                            $hash_info = password_get_info($current_password);
                            
                            if ($hash_info['algo'] === 0) {
                                // Mật khẩu chưa được hash, cập nhật
                                $default_password = $username . '123';
                                $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
                                
                                $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
                                $update_stmt->bind_param("ss", $hashed_password, $username);
                                
                                if ($update_stmt->execute()) {
                                    $updated_count++;
                                }
                            } else {
                                $already_hashed++;
                            }
                        }
                        
                        echo "<h3>Kết quả cập nhật mật khẩu:</h3>";
                        echo "<p>Đã cập nhật: $updated_count người dùng</p>";
                        echo "<p>Đã hash sẵn: $already_hashed người dùng</p>";
                        echo "<p>Mật khẩu mới: [username]123</p>";
                    }
                    break;
                
                case 'delete_user':
                    // Xóa người dùng
                    if (isset($_GET['username'])) {
                        $username_to_delete = $_GET['username'];
                        
                        $delete_stmt = $conn->prepare("DELETE FROM users WHERE username = ?");
                        $delete_stmt->bind_param("s", $username_to_delete);
                        
                        if ($delete_stmt->execute()) {
                            echo "<div style='background-color: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 20px;'>";
                            echo "<h3>Đã xóa người dùng thành công</h3>";
                            echo "<p>Username: $username_to_delete</p>";
                            echo "</div>";
                        } else {
                            echo "<div style='background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 20px;'>";
                            echo "<h3>Lỗi khi xóa người dùng</h3>";
                            echo "<p>Lỗi: " . $delete_stmt->error . "</p>";
                            echo "</div>";
                        }
                    }
                    break;
            }
        } else {
            echo "<p style='color: red;'>Lỗi kết nối CSDL: " . ($conn ? $conn->connect_error : "Không có kết nối") . "</p>";
        }
    } else {
        echo "<p style='color: red;'>Không tìm thấy file cấu hình CSDL!</p>";
    }
}
?>

<p><a href="/183intership/check-and-fix.php">Quay lại trang kiểm tra CSDL</a></p>