<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

class PeriodBuilder extends Builder
{
    private function protect(): void
    {
        Governance::assertWritable();
        $cutoff = Governance::cutoff();
        if ($cutoff) {
            $query = clone $this;
            abort_if($query->where(function ($q) use ($cutoff): void {
                $q->where($this->getModel()->qualifyColumn('created_at'), '<', $cutoff)->orWhereNull($this->getModel()->qualifyColumn('created_at'));
            })->exists(), 423, 'Data periode sebelumnya hanya dapat dilihat.');
        }
    }

    public function update(array $values)
    {
        $this->protect();

        return parent::update($values);
    }

    public function delete()
    {
        $this->protect();

        return parent::delete();
    }

    public function forceDelete()
    {
        $this->protect();

        return parent::forceDelete();
    }
}
