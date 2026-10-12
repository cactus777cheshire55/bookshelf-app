<?php

namespace Tests\Unit;

use App\Enums\ReadingPlanStatus;
use PHPUnit\Framework\TestCase;

class ReadingPlanStatusTest extends TestCase
{
    /**
     * A basic unit test example.
     */

    /** @test */
    public function 各ステータスのラベルが正しく取得できるか(): void
    {
        $this->assertEquals('読書中', ReadingPlanStatus::InProgress->label());
        $this->assertEquals('読了', ReadingPlanStatus::Completed->label());
        $this->assertEquals('期限切れ', ReadingPlanStatus::Expired->label());
    }

    /** @test */
    public function 各ステータスに対応する_cs_sのクラスが合うか(): void
    {
        $this->assertStringContainsString('bg-blue-100', ReadingPlanStatus::InProgress->badgeClass());
        $this->assertStringContainsString('bg-green-100', ReadingPlanStatus::Completed->badgeClass());
        $this->assertStringContainsString('bg-red-100', ReadingPlanStatus::Expired->badgeClass());
    }
}
