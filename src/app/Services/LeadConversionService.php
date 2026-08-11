<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DealStage;
use App\Enums\LeadStatus;
use App\Models\Deal;
use App\Models\Lead;
use App\Repositories\Contracts\CompanyRepositoryInterface;
use App\Repositories\Contracts\ContactRepositoryInterface;
use App\Repositories\Contracts\DealRepositoryInterface;
use App\Repositories\Contracts\LeadRepositoryInterface;
use Illuminate\Support\Facades\DB;

class LeadConversionService
{
    public function __construct(
        protected LeadRepositoryInterface $leads,
        protected ContactRepositoryInterface $contacts,
        protected CompanyRepositoryInterface $companies,
        protected DealRepositoryInterface $deals,
        protected WorkflowEngine $workflowEngine,
    ) {}

    public function convert(Lead $lead, string $companyName, string $dealTitle, float $dealAmount): Deal
    {
        // Everything inside this closure either ALL happens, or NONE of
        // it does. If creating the Deal throws for any reason, the
        // Company and Contact already created in this same call are
        // automatically rolled back too — no half-converted Lead is
        // ever left behind.
        $deal = DB::transaction(function () use ($lead, $companyName, $dealTitle, $dealAmount) {
            $company = $this->companies->create([
                'name' => $companyName,
            ]);

            $contact = $this->contacts->create([
                'name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'company_id' => $company->id,
            ]);

            $deal = $this->deals->create([
                'title' => $dealTitle,
                'amount' => $dealAmount,
                'stage' => DealStage::Prospecting,
                'contact_id' => $contact->id,
                'company_id' => $company->id,
            ]);

            // Mark the original Lead as converted, so it stops showing
            // up as a fresh, unworked lead — but we keep the row. It's
            // real history: proof of where this Deal came from.
            $this->leads->update($lead, ['status' => LeadStatus::Qualified]);

            return $deal;
        });

        // Deliberately AFTER the transaction, not inside it. The Deal is
        // already safely committed by this point — a broken or
        // misconfigured automation rule must never be able to undo a
        // successful Lead conversion. Automation is layered on top of
        // the core business transaction, not a dependency of it.
        $this->workflowEngine->fire('lead.converted', [
            'subject' => $deal,
            'deal_amount' => (float) $deal->amount,
        ]);

        return $deal;
    }
}
