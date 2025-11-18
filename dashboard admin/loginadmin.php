<?php 
require __DIR__ . '/../koneksi.php';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Login Admin - AMertaNUSA</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <!-- Favicon -->
    <link href="img/favicon.ico" rel="icon">

    <!-- Google Web Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Nunito:wght@600;700;800&family=Pacifico&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">

    <!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="css/style.css" rel="stylesheet">

    <style>
        html, body {
            height: 100%;
            margin: 0;
            overflow: hidden;
        }

        body {
            background: linear-gradient(135deg, rgba(15, 23, 43, 1) 0%, rgba(25, 35, 70, 1) 100%);
            font-family: 'Nunito', sans-serif;
        }

        .row.h-100 {
            height: 100vh;
        }

        /* Bagian kiri (logo) */
        .logo-section {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 0;
            background: none;
        }

        .logo-section img {
            max-width: 300px;
            width: 80%;
            filter: drop-shadow(0 0 10px rgba(255, 255, 255, 0.2));
        }

        /* Bagian kanan (login) */
        .login-section {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 0;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 40px 35px;
            width: 100%;
            max-width: 550px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.25);
            color: #fff;
        }

        .login-card h1 {
            font-weight: 800;
            margin-bottom: 10px;
            color: #ffffff;
        }

        .login-card img {
            width: 75px;
            margin-bottom: 8px;
        }

        .login-card h2 {
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 25px;
        }

        .form-floating .form-control {
            border-radius: 10px;
            border: none;
            background: rgba(255, 255, 255, 0.3);
            color: #fff;
        }

        .form-floating label {
            color: rgba(255, 255, 255, 0.85);
        }

        .btn-login {
            background-color: var(--primary);
            border: none;
            width: 100%;
            border-radius: 10px;
            font-weight: 600;
            text-transform: uppercase;
            padding: 10px;
            transition: 0.3s;
            box-shadow: 0 4px 10px rgba(255, 170, 0, 0.3);
        }

        .btn-login:hover {
            background-color: #e68900;
        }

        /* Responsif - logo di atas, form di bawah */
        @media (max-width: 768px) {
            html, body {
                overflow-y: auto;
            }

            .row.h-100 {
                height: auto;
            }

            .logo-section {
                margin-top: 40px;
            }

            .login-section {
                margin-top: 10px;
                margin-bottom: 40px;
            }

            .login-card {
                max-width: 90%;
                padding: 30px 25px;
            }
        }
    </style>
</head>

<body>
  <div class="container-xxl py-5 bg-dark hero-header mb-5">
        <div class="row h-100 g-0"> <!-- g-0: hilangkan jarak antar kolom -->
            <!-- KIRI: Logo -->
            <div class="col-lg-6 d-flex justify-content-center align-items-center logo-section">
                <img src="img/logo-amerta.png" alt="Logo AmertaNUSA" style="width: 90%; animation:none; transform:none;">
            </div>

      <!-- KANAN: Form Login -->
      <div class="col-lg-6 d-flex justify-content-center align-items-center login-section">
        <div class="login-card">
          <h1>Welcome</h1>
          <img src="img/logo-amerta.png" alt="Logo AmertaNUSA">
          <h2>AmertaNUSA</h2>

          <form action="proses_login.php" method="POST" autocomplete="off">
            <div class="form-floating mb-3">
              <input type="text" name="email" class="form-control" id="email" placeholder="Email" required>
              <label for="email">Email</label>
            </div>

            <div class="form-floating mb-4">
              <input type="password" name="password" class="form-control" id="password" placeholder="Password" required>
              <label for="password">Password</label>
            </div>

            <button type="submit" class="btn btn-login text-white">Login</button>
          </form>
        </div>
      </div>

    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Bootstrap JS -->
</body>
</html>
