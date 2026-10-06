<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class OrganizerDashboardQaRegressionTest extends TestCase
{
    public function test_pos_sale_builder_keeps_pass_dates_inside_cart_item_scope(): void
    {
        $source = file_get_contents(__DIR__.'/../../resources/views/organizer/box-office/pos.blade.php');

        $this->assertStringNotContainsString("})});if(l.event_date_ids)", $source);
        $this->assertStringContainsString("if(l.event_date_ids){l.event_date_ids.forEach", $source);
    }

    public function test_shared_dashboard_charts_guard_missing_canvases(): void
    {
        $source = file_get_contents(__DIR__.'/../../public/assets/admin/js/chart-init.js');

        $this->assertStringContainsString("if (incomeCanvas)", $source);
        $this->assertStringContainsString("if (eventBookingCanvas)", $source);
        $this->assertStringContainsString("if (productIncomeCanvas)", $source);
        $this->assertStringContainsString("if (productOrderCanvas)", $source);
    }

    public function test_box_office_manage_link_uses_effective_event_type(): void
    {
        $source = file_get_contents(__DIR__.'/../../resources/views/organizer/event/index.blade.php');

        $this->assertStringContainsString('$effectiveEventType', $source);
        $this->assertStringContainsString("'event_type' => \$effectiveEventType", $source);
    }

    public function test_transactions_page_uses_payment_orders_as_current_payment_history(): void
    {
        $controller = file_get_contents(__DIR__.'/../../app/Http/Controllers/BackEnd/Organizer/OrganizerController.php');
        $view = file_get_contents(__DIR__.'/../../resources/views/organizer/transaction.blade.php');

        $this->assertStringContainsString("PaymentOrder::where('organizer_id'", $controller);
        $this->assertStringContainsString("Payment Transactions", $view);
        $this->assertStringNotContainsString("NO TRANSCATION FOUND", $view);
    }
}
