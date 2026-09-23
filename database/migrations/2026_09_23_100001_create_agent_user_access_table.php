<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Optional fine-grained narrowing WITHIN a company the user already has
        // company_user_access to. If a user has zero rows here for a given company's
        // agents, they see every agent that company has (the simple default). The
        // moment they have at least one row for that company, they see only the
        // agent(s) listed — this is what lets a demo grant show exactly one assistant
        // out of a customer that may have several.
        Schema::create('agent_user_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'agent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_user_access');
    }
};
