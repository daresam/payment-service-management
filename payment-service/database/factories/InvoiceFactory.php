<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 50);
        $rate = $this->faker->randomFloat(2, 10, 500);
        $paymentTerms = $this->faker->randomElement(['Net 7', 'Net 14', 'Net 30']);
        $dueDate = $this->faker->dateTimeBetween('+1 day', '+30 days');
        
        // Calculate issue date based on due date and payment terms
        $issueDate = clone $dueDate;
        $days = match($paymentTerms) {
            'Net 7' => 7,
            'Net 14' => 14,
            'Net 30' => 30,
        };
        $issueDate->modify("-{$days} days");
        
        return [
            'corporate_id' => 1,
            'vendor_id' => 1,
            'invoice_number' => 'INV-' . $this->faker->unique()->regexify('[A-Z0-9]{8}'),
            'quantity' => $quantity,
            'rate' => $rate,
            'amount' => $quantity * $rate,
            'status' => $this->faker->randomElement(['OPEN', 'CLOSED']),
            'issue_date' => $issueDate->format('Y-m-d'),
            'due_date' => $dueDate->format('Y-m-d'),
            'payment_terms' => $paymentTerms,
            'description' => $this->faker->sentence(4),
        ];
    }
}

