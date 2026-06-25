<?php
// logica_biopsias.php

// Función pura para validar credenciales. No redirecciona, solo devuelve datos.
function verificarLogin($conn, $username, $password) {
    // Si los campos están vacíos, rechazamos de inmediato sin consultar a la BD
    if (empty($username) || empty($password)) {
        return false;
    }

    try {
        $stmt = $conn->prepare("SELECT id_usuario, username, rol FROM Usuarios WHERE username = ? AND password = ?");
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res->num_rows > 0) {
            $usuario = $res->fetch_assoc();
            $stmt->close();
            return $usuario; // Retorna los datos si es exitoso
        }
        
        $stmt->close();
        return false; // Retorna falso si no coincide la contraseña
        
    } catch (Throwable $e) {
        // Si la base de datos se cae, evitamos que el sistema explote
        return false; 
    }
}

function procesarAcciones($conn) {
    if (!isset($_SESSION['user'])) return null;

    // 1. Guardar Paciente
    if (isset($_POST['save_paciente'])) {
        try {
            $stmt = $conn->prepare("INSERT INTO Pacientes (nombre, apellido, rut) VALUES (?, ?, ?) 
                                    ON DUPLICATE KEY UPDATE nombre=?, apellido=?");
            $stmt->bind_param("sssss", $_POST['nombre'], $_POST['apellido'], $_POST['rut'], $_POST['nombre'], $_POST['apellido']);
            $stmt->execute();
            $stmt->close();
            return ["msg" => "Paciente guardado correctamente.", "type" => "success"];
        } catch (Throwable $e) {
            return ["msg" => "Error: Hubo un problema al guardar el paciente. Verifique los datos.", "type" => "danger"];
        }
    }

// 2. Guardar Biopsia (Con Estado y Fecha de Salida)
    if (isset($_POST['save_biopsia'])) {
        try {
            $idp = (int)$_POST['id_paciente']; 
            $org = $_POST['organo']; 
            $fec = $_POST['fecha_ingreso']; 
            $obs = $_POST['observaciones'];
            $est = $_POST['estado'] ?? 'Pendiente'; 
            $idb = $_POST['id_biopsia_edit'];

            // Si se concluye, se registra la fecha actual. Si no, queda vacía.
            $fec_salida = ($est === 'Concluida') ? date('Y-m-d') : null;

            if (!empty($idb)) {
                $stmt = $conn->prepare("UPDATE Biopsias SET id_paciente=?, organo=?, fecha_ingreso=?, observaciones=?, estado=?, fecha_salida=? WHERE id_biopsia=?");
                // "isssssi" = int, string, string, string, string, string(null), int
                $stmt->bind_param("isssssi", $idp, $org, $fec, $obs, $est, $fec_salida, $idb);
            } else {
                $stmt = $conn->prepare("INSERT INTO Biopsias (id_paciente, organo, fecha_ingreso, observaciones, estado, fecha_salida) VALUES (?, ?, ?, ?, ?, ?)");
                // "isssss" = int, string, string, string, string, string(null)
                $stmt->bind_param("isssss", $idp, $org, $fec, $obs, $est, $fec_salida);
            }
            $stmt->execute();
            $stmt->close();
            return ["msg" => "Operación exitosa.", "type" => "success"];
        } catch (Throwable $e) {
            return ["msg" => "Error al procesar la biopsia. Verifique los datos.", "type" => "danger"];
        }
    }
    // Acción Rápida: Concluir Biopsia directamente desde la tabla
    if (isset($_POST['conclude_biopsia'])) {
        try {
            $idb = (int)$_POST['id_biopsia'];
            $fec_salida = date('Y-m-d');
            $est = 'Concluida';

            $stmt = $conn->prepare("UPDATE Biopsias SET estado=?, fecha_salida=? WHERE id_biopsia=?");
            $stmt->bind_param("ssi", $est, $fec_salida, $idb);
            $stmt->execute();
            $stmt->close();
            
            return ["msg" => "Biopsia finalizada exitosamente.", "type" => "success"];
        } catch (Throwable $e) {
            return ["msg" => "Error al concluir la biopsia.", "type" => "danger"];
        }
    }

    // 3. Eliminar Biopsia 
    if (isset($_POST['delete_biopsia'])) {
        if ($_SESSION['rol'] == 'JEFE') {
            $id = (int)$_POST['id_biopsia'];
            $conn->query("DELETE FROM Biopsias WHERE id_biopsia=$id");
            return ["msg" => "Biopsia eliminada permanentemente.", "type" => "warning"];
        } else {
            return ["msg" => "Error: No tienes permisos para eliminar.", "type" => "danger"];
        }
    }

    // 4. Guardar Usuario
    if (isset($_POST['save_usuario']) && $_SESSION['rol'] == 'JEFE') {
        try {
            $usr = $_POST['username_new'];
            $pwd = $_POST['password_new']; 
            $rol = $_POST['rol_new'];
            $idu = $_POST['id_usuario_edit'];

            if (!empty($idu)) {
                $stmt = $conn->prepare("UPDATE Usuarios SET username=?, password=?, rol=? WHERE id_usuario=?");
                $stmt->bind_param("sssi", $usr, $pwd, $rol, $idu);
            } else {
                $stmt = $conn->prepare("INSERT INTO Usuarios (username, password, rol) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $usr, $pwd, $rol);
            }
            $stmt->execute();
            $stmt->close();
            return ["msg" => "Usuario guardado exitosamente.", "type" => "success"];
        } catch (Throwable $e) {
            return ["msg" => "Error al guardar: Verifique que el nombre de usuario no esté repetido.", "type" => "danger"];
        }
    }

    // 5. Eliminar Usuario
    if (isset($_POST['delete_usuario']) && $_SESSION['rol'] == 'JEFE') {
        try {
            $id = (int)$_POST['id_usuario'];
            if ($id == $_SESSION['id']) {
                return ["msg" => "Operación denegada: No puedes eliminar tu propia cuenta activa.", "type" => "danger"];
            } else {
                $stmt = $conn->prepare("DELETE FROM Usuarios WHERE id_usuario=?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
                return ["msg" => "Usuario eliminado correctamente.", "type" => "warning"];
            }
        } catch (Throwable $e) {
            return ["msg" => "Error al eliminar el usuario.", "type" => "danger"];
        }
    }

    return null;
}
?>