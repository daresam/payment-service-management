<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicesTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('corporate_id');
                $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
                $table->string('invoice_number')->unique();
                $table->integer('quantity')->default(1);
                $table->decimal('rate', 10, 2);
                $table->decimal('amount', 10, 2); // Total cost: quantity * rate
                $table->enum('status', ['OPEN', 'CLOSED'])->default('OPEN');
                $table->date('issue_date'); // Date invoice is issued
                $table->date('due_date'); // Due date based on payment terms
                $table->string('payment_terms')->default('Net 30'); // e.g., Net 7, Net 14, Net 30
                $table->text('description')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('invoices');
    }
}
