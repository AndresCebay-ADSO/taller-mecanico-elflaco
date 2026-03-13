<?php

it('la raíz redirige a login si no hay sesión', function () {
    $response = $this->get('/');

    // '/' → '/dashboard' → '/login' (dos redirects)
    $response->assertRedirect();
});

