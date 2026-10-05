<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('organizer_payment_profiles', function (Blueprint $table) {
            $table->string('business_type', 32)->nullable()->after('organizer_id');
            $table->string('legal_business_name')->nullable();
            $table->string('brand_name')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 32)->nullable();
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('address_city')->nullable();
            $table->string('address_state')->nullable();
            $table->string('address_postcode', 16)->nullable();
            $table->string('address_country', 2)->default('IN');
            $table->string('business_category')->nullable();
            $table->string('business_subcategory')->nullable();
            $table->text('pan')->nullable();
            $table->text('stakeholder_pan')->nullable();
            $table->text('gstin')->nullable()->change();
            $table->string('bank_account_holder')->nullable();
            $table->string('bank_ifsc', 16)->nullable();
            $table->string('bank_account_last4', 4)->nullable();
            $table->string('kyc_status', 32)->default('draft')->index();
            $table->json('kyc_remarks')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->string('razorpay_stakeholder_id')->nullable()->index();
            $table->string('razorpay_product_id')->nullable()->index();
        });
    }

    public function down(): void {
        Schema::table('organizer_payment_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'business_type','legal_business_name','brand_name','contact_name','contact_email','contact_phone',
                'address_line1','address_line2','address_city','address_state','address_postcode','address_country',
                'business_category','business_subcategory','pan','stakeholder_pan','bank_account_holder','bank_ifsc',
                'bank_account_last4','kyc_status','kyc_remarks','submitted_at','activated_at',
                'razorpay_stakeholder_id','razorpay_product_id'
            ]);
        });
    }
};