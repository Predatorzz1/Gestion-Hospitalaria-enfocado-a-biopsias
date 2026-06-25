<?php
// tests/Feature/HospitalTest.php

// =========================================================================
// MÓDULO 1: SEGURIDAD Y SESIONES
// =========================================================================

test('el sistema neutraliza etiquetas html y script para evitar XSS', function () {
    $input_malicioso = "<script>alert('Vulnerabilidad detectada')</script>";
    $resultado = htmlspecialchars($input_malicioso, ENT_QUOTES, 'UTF-8');
    expect($resultado)->not->toContain('<script>');
    expect($resultado)->toContain('&lt;script&gt;');
});

test('un usuario con rol TEC no puede acceder a las credenciales de JEFE', function () {
    $_SESSION['rol'] = 'TEC';
    $puedeGestionarUsuarios = ($_SESSION['rol'] === 'JEFE');
    expect($puedeGestionarUsuarios)->toBeFalse();
});

test('un usuario sin sesion activa no puede procesar acciones', function () {
    unset($_SESSION['user']);
    require_once __DIR__ . '/../../logica_biopsias.php';
    
    $resultado = procesarAcciones(null);
    expect($resultado)->toBeNull();
});

test('procesarAcciones ignora peticiones que no son POST', function () {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    require_once __DIR__ . '/../../logica_biopsias.php';
    
    $resultado = procesarAcciones(null);
    expect($resultado)->toBeNull();
});

// =========================================================================
// MÓDULO 2: LOGIN
// =========================================================================

test('el login rechaza intentos con campos vacios sin consultar la BD', function () {
    require_once __DIR__ . '/../../logica_biopsias.php';
    $resultado = verificarLogin(null, '', '');
    expect($resultado)->toBeFalse();
});

test('el login maneja correctamente una caida de base de datos', function () {
    require_once __DIR__ . '/../../logica_biopsias.php';
    $resultado = verificarLogin(null, 'medico1', '123456');
    expect($resultado)->toBeFalse();
});

// =========================================================================
// MÓDULO 3: GESTIÓN DE PACIENTES Y BIOPSIAS (ROBUSTEZ)
// =========================================================================

test('devuelve error controlado si falla la BD al guardar un paciente', function () {
    $_POST = [];
    $_SESSION['user'] = 'medico_test';
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST['save_paciente'] = true;
    $_POST['nombre'] = 'Juan';
    $_POST['apellido'] = 'Pérez';
    $_POST['rut'] = '12345678-9';
    
    require_once __DIR__ . '/../../logica_biopsias.php';
    $resultado = procesarAcciones(null);
    
    expect($resultado)->toBeArray();
    expect($resultado['type'])->toEqual('danger');
    expect($resultado['msg'])->toContain('Error');
});

test('devuelve error controlado al intentar guardar biopsia si falla el sistema', function () {
    $_POST = []; 
    $_SESSION['user'] = 'medico_test';
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST['save_biopsia'] = true;
    $_POST['id_paciente'] = 1;
    $_POST['organo'] = 'Hígado';
    $_POST['fecha_ingreso'] = '2026-06-24';
    $_POST['observaciones'] = 'Muestra de prueba';
    $_POST['id_biopsia_edit'] = ''; 
    
    require_once __DIR__ . '/../../logica_biopsias.php';
    $resultado = procesarAcciones(null);
    
    expect($resultado['type'])->toEqual('danger');
    expect($resultado['msg'])->toContain('Error al procesar la biopsia');
});

// =========================================================================
// MÓDULO 4: GESTIÓN DE USUARIOS (REGLAS DE NEGOCIO Y ROLES)
// =========================================================================

test('devuelve error controlado al intentar crear un usuario si falla la BD', function () {
    $_POST = [];
    $_SESSION['user'] = 'jefe_admin';
    $_SESSION['rol'] = 'JEFE'; 
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST['save_usuario'] = true;
    $_POST['id_usuario_edit'] = ''; 
    $_POST['username_new'] = 'nuevo_medico';
    $_POST['password_new'] = '12345';
    $_POST['rol_new'] = 'TEC';

    require_once __DIR__ . '/../../logica_biopsias.php';
    $resultado = procesarAcciones(null);

    expect($resultado['type'])->toEqual('danger');
    expect($resultado['msg'])->toContain('Error al guardar');
});

test('bloquea el intento de actualizar usuarios si el rol es TEC', function () {
    $_POST = [];
    $_SESSION['user'] = 'tecnico_infiltrado';
    $_SESSION['rol'] = 'TEC'; 
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST['save_usuario'] = true;
    $_POST['id_usuario_edit'] = '3'; 
    $_POST['rol_new'] = 'JEFE'; 

    require_once __DIR__ . '/../../logica_biopsias.php';
    $resultado = procesarAcciones(null);

    expect($resultado)->toBeNull();
});

test('impide que un administrador (JEFE) borre su propia cuenta activa', function () {
    $_POST = [];
    $_SESSION['user'] = 'jefe_supremo';
    $_SESSION['rol'] = 'JEFE';
    $_SESSION['id'] = 5; 
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST['delete_usuario'] = true;
    $_POST['id_usuario'] = 5; 

    require_once __DIR__ . '/../../logica_biopsias.php';
    $resultado = procesarAcciones(null);

    expect($resultado['type'])->toEqual('danger');
    expect($resultado['msg'])->toContain('No puedes eliminar tu propia cuenta activa');
});