
<?php
require 'logica_biopsias.php';
session_start();


// CONEXIÓN MYSQL
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'HospitalTalca');

function getDB() {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset("utf8");
        return $conn;
    } catch (Exception $e) {
        die("Error crítico: No se pudo conectar a la base de datos."); 
    }
}

$error = "";

// LOGIN MEJORADO
if (isset($_POST['login'])) {
    $conn = getDB();
    $u = trim($_POST['username'] ?? '');
    $p = trim($_POST['password'] ?? '');
    
    // Usamos nuestra nueva función "testeable"
    $usuario = verificarLogin($conn, $u, $p);

    if ($usuario) {
        $_SESSION['user'] = $usuario['username'];
        $_SESSION['rol']  = $usuario['rol']; 
        $_SESSION['id']   = $usuario['id_usuario'];
        
        header("Location: index.php"); 
        exit;
    } else {
        $error = "Usuario, contraseña incorrectos o error de conexión.";
    }
}

// LOGOUT
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

$msg = ""; $msgType = "";
if (isset($_SESSION['user'])) {
    $conn = getDB();
    // Ejecutamos la función centralizada
    $resultadoAccion = procesarAcciones($conn);
    
    if ($resultadoAccion) {
        $msg = $resultadoAccion['msg'];
        $msgType = $resultadoAccion['type'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Hospital Talca | Biopsias</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">

<?php if (!isset($_SESSION['user'])): ?>
    <div class="login-page">
        <div class="login-box">
            <div class="card card-outline card-primary">
                <div class="card-header text-center"><h1><b>Hosp</b>Talca</h1></div>
                <div class="card-body">
                    <p class="login-box-msg">Inicie sesión para gestionar</p>
                    <?php if($error): ?><p class='text-danger text-center'><?= $error ?></p><?php endif; ?>
                    <form method="post">
                        <div class="input-group mb-3">
                            <input type="text" name="username" class="form-control" placeholder="Usuario" required>
                            <div class="input-group-append"><div class="input-group-text"><span class="fas fa-user"></span></div></div>
                        </div>
                        <div class="input-group mb-3">
                            <input type="password" name="password" class="form-control" placeholder="Contraseña" required>
                            <div class="input-group-append"><div class="input-group-text"><span class="fas fa-lock"></span></div></div>
                        </div>
                        <button type="submit" name="login" class="btn btn-primary btn-block">Ingresar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php else: 
    $view = $_GET['view'] ?? 'dashboard';
    $conn = getDB();
?>
    <div class="wrapper">
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <ul class="navbar-nav"><li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a></li></ul>
            <ul class="navbar-nav ml-auto">
                <li class="nav-item"><span class="nav-link text-muted">Usuario: <b><?= $_SESSION['user'] ?></b></span></li>
                <li class="nav-item"><a class="nav-link text-danger" href="?logout=1"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
            </ul>
        </nav>

        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <a href="#" class="brand-link"><span class="brand-text font-weight-light pl-3">Sistema <b>Biopsias</b></span></a>
            <div class="sidebar">
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column">
                        <li class="nav-item"><a href="?view=dashboard" class="nav-link <?= $view=='dashboard'?'active':'' ?>"><i class="nav-icon fas fa-tachometer-alt"></i> <p>Dashboard</p></a></li>
                        <li class="nav-item"><a href="?view=gestion" class="nav-link <?= $view=='gestion'?'active':'' ?>"><i class="nav-icon fas fa-edit"></i> <p>Gestión</p></a></li>

                        <?php if($_SESSION['rol'] == 'JEFE'): ?>
                        <li class="nav-item">
                            <a href="?view=usuarios" class="nav-link <?= $view=='usuarios'?'active':'' ?>">
                                <i class="nav-icon fas fa-users-cog"></i> <p>Usuarios</p>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </aside>

        <div class="content-wrapper">
            <div class="content-header"><div class="container-fluid"><h1><?= ucfirst($view) ?></h1></div></div>
            <section class="content">
                <div class="container-fluid">
                    <?php if($msg): ?><div class="alert alert-<?= $msgType ?> alert-dismissible"><button class="close" data-dismiss="alert">&times;</button><?= $msg ?></div><?php endif; ?>

                    <?php 
                    // ---------------- VISTA DASHBOARD ----------------
                    if ($view == 'dashboard'): 
                        $totalPac = $conn->query("SELECT COUNT(*) c FROM Pacientes")->fetch_object()->c;
                        
                        $sqlStats = "SELECT 
                            SUM(CASE WHEN DATEDIFF(fecha_expiracion, NOW()) < 0 THEN 1 ELSE 0 END) as vencidas,
                            SUM(CASE WHEN DATEDIFF(fecha_expiracion, NOW()) BETWEEN 0 AND 5 THEN 1 ELSE 0 END) as peligro,
                            SUM(CASE WHEN DATEDIFF(fecha_expiracion, NOW()) BETWEEN 6 AND 10 THEN 1 ELSE 0 END) as alerta
                            FROM Biopsias";
                        $stats = $conn->query($sqlStats)->fetch_assoc();
                    ?>
                        <div class="row">
                            <div class="col-lg-3 col-6"><div class="small-box bg-info"><div class="inner"><h3><?= $totalPac ?></h3><p>Pacientes Totales</p></div><div class="icon"><i class="fas fa-users"></i></div></div></div>
                            <div class="col-lg-3 col-6"><div class="small-box bg-warning"><div class="inner"><h3><?= (int)$stats['alerta'] ?></h3><p>En Alerta (6-10 días)</p></div><div class="icon"><i class="fas fa-exclamation"></i></div></div></div>
                            <div class="col-lg-3 col-6"><div class="small-box bg-orange"><div class="inner"><h3><?= (int)$stats['peligro'] ?></h3><p>En Peligro (< 5 días)</p></div><div class="icon"><i class="fas fa-radiation-alt"></i></div></div></div>
                            <div class="col-lg-3 col-6"><div class="small-box bg-danger"><div class="inner"><h3><?= (int)$stats['vencidas'] ?></h3><p>Ya Vencidas</p></div><div class="icon"><i class="fas fa-skull-crossbones"></i></div></div></div>
                        </div>

                        <div class="card card-outline card-secondary">
                            <div class="card-header"><h3 class="card-title">Prioridad de Atención</h3></div>
                            <div class="card-body table-responsive p-0">
                                <table class="table table-striped text-nowrap">
                                    <thead><tr><th>ID</th><th>Órgano</th><th>Vence en...</th><th>Estado</th></tr></thead>
                                    <tbody>
                                    <?php
                                    $sqlDash = "SELECT b.*, DATEDIFF(b.fecha_expiracion, NOW()) as dias_restantes 
                                                FROM Biopsias b ORDER BY dias_restantes ASC LIMIT 10";
                                    $resDash = $conn->query($sqlDash);
                                    while($d = $resDash->fetch_assoc()):
                                        $days = $d['dias_restantes'];
                                        if ($days < 0) { $badge="danger"; $txt="VENCIDA (".abs($days)." días)"; }
                                        elseif ($days <= 5) { $badge="warning"; $txt="PELIGRO"; }
                                        elseif ($days <= 10) { $badge="warning"; $txt="ALERTA"; }
                                        else { $badge="success"; $txt="OK"; }
                                    ?>
                                        <tr>
                                            <td>BIO-<?= $d['id_biopsia'] ?></td>
                                            <td><?= $d['organo'] ?></td>
                                            <td class="font-weight-bold text-<?= $days<0?'danger': ($days<=5?'orange':'dark') ?>"><?= $days < 0 ? 0 : $days ?> días</td>
                                            <td><span class="badge badge-<?= $badge ?>"><?= $txt ?></span></td>
                                        </tr>
                                    <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    <?php 
                    // ---------------- VISTA GESTION ----------------
                    elseif ($view == 'gestion'): 
                        $filtro = "";
                        if (!empty($_GET['q'])) {
                            $q = $conn->real_escape_string($_GET['q']);
                            $filtro = " WHERE p.rut LIKE '%$q%' OR p.apellido LIKE '%$q%' OR b.organo LIKE '%$q%' ";
                        }
                    ?>
                        <div class="card">
                            <div class="card-header">
                                <form class="row">
                                    <input type="hidden" name="view" value="gestion">
                                    <div class="col-md-5"><input type="text" name="q" class="form-control" placeholder="Buscar RUT, Apellido..." value="<?= $_GET['q']??'' ?>"></div>
                                    <div class="col-md-2"><button class="btn btn-default btn-block"><i class="fas fa-search"></i> Buscar</button></div>
                                    <div class="col-md-5 text-right">
                                        <button type="button" class="btn btn-secondary" data-toggle="modal" data-target="#modal-paciente">Nuevo Paciente</button>
                                        <button type="button" class="btn btn-primary" onclick="editarBiopsia()">Nueva Biopsia</button>
                                    </div>
                                </form>
                            </div>
                            <div class="card-body p-0 table-responsive">
                                <table class="table table-hover text-nowrap">
                                    <thead><tr><th>ID</th><th>Paciente</th><th>Órgano</th><th>Ingreso</th><th>Acción</th></tr></thead>
                                    <tbody>
                                    <?php
                                    $sqlList = "SELECT b.*, p.rut, p.nombre, p.apellido 
                                                FROM Biopsias b JOIN Pacientes p ON b.id_paciente=p.id_paciente 
                                                $filtro ORDER BY b.id_biopsia DESC LIMIT 50";
                                    $resList = $conn->query($sqlList);
                                    while($r = $resList->fetch_assoc()):
                                    ?>
                                        <tr>
                                            <td><?= $r['id_biopsia'] ?></td>
                                            <td><?= htmlspecialchars($r['nombre'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($r['apellido'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= $r['organo'] ?></td>
                                            <td><?= date('d/m/Y', strtotime($r['fecha_ingreso'])) ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-info" onclick="editarBiopsia(<?= htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8') ?>)"><i class="fas fa-edit"></i></button>
                                                <?php if($_SESSION['rol'] == 'JEFE'): ?>
                                                <form method="post" class="d-inline" onsubmit="return confirm('¿Seguro que desea eliminar?');">
                                                    <input type="hidden" name="id_biopsia" value="<?= $r['id_biopsia'] ?>">
                                                    <button type="submit" name="delete_biopsia" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                                </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
  
                    <?php 
                    // ---------------- VISTA USUARIOS ----------------
                    elseif ($view == 'usuarios' && $_SESSION['rol'] == 'JEFE'): 
                    ?>
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title mt-1">Gestión de Accesos</h3>
                                <div class="card-tools"><button type="button" class="btn btn-primary btn-sm" onclick="editarUsuario()">Nuevo Usuario</button></div>
                            </div>
                            <div class="card-body p-0 table-responsive">
                                <table class="table table-hover text-nowrap">
                                    <thead><tr><th>ID</th><th>Username</th><th>Rol</th><th>Acciones</th></tr></thead>
                                    <tbody>
                                    <?php
                                    $resUsr = $conn->query("SELECT id_usuario, username, rol FROM Usuarios");
                                    while($u = $resUsr->fetch_assoc()):
                                    ?>
                                        <tr>
                                            <td><?= $u['id_usuario'] ?></td>
                                            <td><?= htmlspecialchars($u['username'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><span class="badge badge-<?= $u['rol']=='JEFE'?'primary':'info' ?>"><?= $u['rol'] ?></span></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-info" onclick="editarUsuario(<?= htmlspecialchars(json_encode($u), ENT_QUOTES, 'UTF-8') ?>)"><i class="fas fa-edit"></i></button>
                                                <?php if($u['id_usuario'] != $_SESSION['id']): ?>
                                                <form method="post" class="d-inline" onsubmit="return confirm('¿Seguro que desea eliminar a este usuario?');">
                                                    <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
                                                    <button type="submit" name="delete_usuario" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                                </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
        
        <div class="modal fade" id="modal-paciente"><div class="modal-dialog"><div class="modal-content"><form method="post">
            <div class="modal-header bg-secondary"><h5 class="modal-title">Registrar Paciente</h5><button class="close" data-dismiss="modal">&times;</button></div>
            <div class="modal-body">
                <input class="form-control mb-2" name="rut" placeholder="RUT (12345678-9)" required>
                <input class="form-control mb-2" name="nombre" placeholder="Nombre" required>
                <input class="form-control mb-2" name="apellido" placeholder="Apellido" required>
            </div>
            <div class="modal-footer"><button type="submit" name="save_paciente" class="btn btn-secondary">Guardar</button></div>
        </form></div></div></div>

        <div class="modal fade" id="modal-biopsia"><div class="modal-dialog"><div class="modal-content"><form method="post">
            <div class="modal-header bg-primary"><h5 class="modal-title">Datos Biopsia</h5><button class="close" data-dismiss="modal">&times;</button></div>
            <div class="modal-body">
                <input type="hidden" name="id_biopsia_edit" id="id_biopsia_edit">
                <label>Paciente:</label>
                <select name="id_paciente" id="id_paciente" class="form-control mb-2" required>
                    <?php $ps = $conn->query("SELECT * FROM Pacientes"); while($p=$ps->fetch_assoc()) echo "<option value='{$p['id_paciente']}'>{$p['rut']} - {$p['apellido']}</option>"; ?>
                </select>
                <label>Órgano:</label>
                <select name="organo" id="organo" class="form-control mb-2">
                    <option>Hígado</option><option>Riñón</option><option>Pulmón</option><option>Estómago</option><option>Piel</option><option>Tiroides</option><option>Próstata</option>
                </select>
                <label>Fecha Ingreso:</label>
                <input type="date" name="fecha_ingreso" id="fecha_ingreso" class="form-control mb-2" required>
                <label>Observaciones:</label>
                <textarea name="observaciones" id="observaciones" class="form-control"></textarea>
            </div>
            <div class="modal-footer"><button type="submit" name="save_biopsia" class="btn btn-primary">Guardar</button></div>
        </form></div></div></div>

        <div class="modal fade" id="modal-usuario"><div class="modal-dialog"><div class="modal-content"><form method="post">
            <div class="modal-header bg-dark"><h5 class="modal-title">Datos de Usuario</h5><button class="close" data-dismiss="modal">&times;</button></div>
            <div class="modal-body">
                <input type="hidden" name="id_usuario_edit" id="id_usuario_edit">
                <label>Nombre de Usuario (Login):</label>
                <input type="text" name="username_new" id="username_new" class="form-control mb-2" required>
                <label>Contraseña:</label>
                <input type="text" name="password_new" id="password_new" class="form-control mb-2" required>
                <label>Rol de Sistema:</label>
                <select name="rol_new" id="rol_new" class="form-control mb-2" required>
                    <option value="TEC">TEC (Médico / Operador)</option>
                    <option value="JEFE">JEFE (Administrador)</option>
                </select>
            </div>
            <div class="modal-footer"><button type="submit" name="save_usuario" class="btn btn-dark">Guardar</button></div>
        </form></div></div></div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    <script>
        function editarBiopsia(data=null) {
            if(data) {
                $('#id_biopsia_edit').val(data.id_biopsia);
                $('#id_paciente').val(data.id_paciente);
                $('#organo').val(data.organo);
                $('#fecha_ingreso').val(data.fecha_ingreso);
                $('#observaciones').val(data.observaciones);
            } else {
                $('#id_biopsia_edit').val('');
                $('#fecha_ingreso').val('<?= date('Y-m-d') ?>');
                $('#observaciones').val('');
            }
            $('#modal-biopsia').modal('show');
        }
        function editarUsuario(data=null) {
            if(data) {
                $('#id_usuario_edit').val(data.id_usuario);
                $('#username_new').val(data.username);
                $('#password_new').val('');
                $('#rol_new').val(data.rol);
            } else {
                $('#id_usuario_edit').val('');
                $('#username_new').val('');
                $('#password_new').val('');
                $('#rol_new').val('TEC');
            }
            $('#modal-usuario').modal('show');
        }
    </script>
<?php endif; ?>
</body>
</html>