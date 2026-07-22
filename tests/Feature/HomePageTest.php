<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomePageTest extends TestCase
{
    public function test_home_page_renders_the_application_shell(): void
    {
        $this->withoutVite();

        $this->get('/')
            ->assertOk()
            ->assertSee('Buku Tamu Digital')
            ->assertSee('Pelayanan tamu yang tercatat, jelas, dan terhubung.')
            ->assertSee(route('health'));
    }
}
