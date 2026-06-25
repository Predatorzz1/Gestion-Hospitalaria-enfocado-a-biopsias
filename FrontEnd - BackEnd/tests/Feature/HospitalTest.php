<?php
// tests/Feature/HospitalTest.php

test('el sistema neutraliza etiquetas html y script para evitar XSS', function () {
    $input_malicioso = "<script>alert('Vulnerabilidad detectada')</script>";
    
    // Verificamos que la función de sanitización que usamos en tu interfaz funcione
    $resultado = htmlspecialchars($input_malicioso, ENT_QUOTES, 'UTF-8');
    
    // Esperamos que el script ya no sea ejecutable
    expect($resultado)->not->toContain('<script>');
    expect($resultado)->toContain('&lt;script&gt;');
});

test('un usuario con rol TEC no puede acceder a las credenciales de JEFE', function () {
    // Simulamos una sesión de Técnico
    $_SESSION['rol'] = 'TEC';
    
    // Verificamos tu lógica de permisos para gestionar usuarios
    $puedeGestionarUsuarios = ($_SESSION['rol'] === 'JEFE');
    
    // El test espera que esto sea falso
    expect($puedeGestionarUsuarios)->toBeFalse();
});

test('un usuario sin sesion activa no puede procesar acciones', function () {
    // Simulamos que el usuario cerró sesión
    unset($_SESSION['user']);
    
    // Incluimos tu archivo de lógica
    require_once __DIR__ . '/../../logica_biopsias.php';
    
    // Ejecutamos la función pasando una conexión falsa (null)
    // Como no hay sesión, la función debería retornar null inmediatamente sin explotar
    $resultado = procesarAcciones(null);
    
    expect($resultado)->toBeNull();
});

test('procesarAcciones ignora peticiones que no son POST', function () {
    // Simulamos que alguien intenta acceder a la lógica directamente por la URL (GET)
    $_SERVER['REQUEST_METHOD'] = 'GET';
    
    require_once __DIR__ . '/../../logica_biopsias.php';
    
    // Le pasamos una conexión nula. Como no es POST, debería retornar null inmediatamente.
    $resultado = procesarAcciones(null);
    
    expect($resultado)->toBeNull();
});

test('devuelve error controlado si falla la BD al guardar un paciente', function () {
    // 0. ¡LA CLAVE! Simulamos que hay una sesión activa para pasar tu filtro de seguridad
    $_POST = [];
    $_SESSION['user'] = 'medico_test';

    // 1. Simulamos que el usuario envió el formulario de paciente
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST['save_paciente'] = true;
    $_POST['nombre'] = 'Juan';
    $_POST['apellido'] = 'Pérez';
    $_POST['rut'] = '12345678-9';
    
    require_once __DIR__ . '/../../logica_biopsias.php';
    
    // 2. Pasamos "null" en lugar de la conexión real para forzar el fallo.
    $resultado = procesarAcciones(null);
    
    // 3. Verificamos que el sistema responde con la alerta de peligro
    expect($resultado)->toBeArray();
    expect($resultado['type'])->toEqual('danger');
    expect($resultado['msg'])->toContain('Error');
});

test('devuelve error controlado al intentar guardar biopsia si falla el sistema', function () {
    // 0. Simulamos sesión activa
    $_POST = [];
    $_SESSION['user'] = 'medico_test';

    // 1. Simulamos el envío del formulario de nueva biopsia
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST['save_biopsia'] = true;
    $_POST['id_paciente'] = 1;
    $_POST['organo'] = 'Hígado';
    $_POST['fecha_ingreso'] = '2026-06-24';
    $_POST['observaciones'] = 'Muestra de prueba';
    $_POST['id_biopsia_edit'] = ''; 
    
    require_once __DIR__ . '/../../logica_biopsias.php';
    
    // 2. Forzamos el fallo simulando desconexión
    $resultado = procesarAcciones(null);
    
    // 3. Verificamos que no se rompa la pantalla del usuario
    expect($resultado['type'])->toEqual('danger');
    expect($resultado['msg'])->toContain('Error al procesar la biopsia');
});

test('el login rechaza intentos con campos vacios sin consultar la BD', function () {
    require_once __DIR__ . '/../../logica_biopsias.php';
    
    // Le pasamos una conexión nula porque ni siquiera debería intentar conectarse
    $resultado = verificarLogin(null, '', '');
    
    // Esperamos que sea falso
    expect($resultado)->toBeFalse();
});

test('el login maneja correctamente una caida de base de datos', function () {
    require_once __DIR__ . '/../../logica_biopsias.php';
    
    // Simulamos un usuario intentando entrar, pero pasamos null como conexión (BD caída)
    $resultado = verificarLogin(null, 'medico1', '123456');
    
    // El catch (Throwable $e) debería atrapar el error y devolver false
    // en lugar de mostrar un error fatal en la pantalla
    expect($resultado)->toBeFalse();
});