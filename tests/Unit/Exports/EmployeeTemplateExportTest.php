<?php

namespace Tests\Unit\Exports;

use App\Exports\EmployeeTemplateExport;
use Tests\TestCase;

class EmployeeTemplateExportTest extends TestCase
{
    public function test_template_contains_matching_headings_and_sample_rows(): void
    {
        $export = new EmployeeTemplateExport();
        $headings = $export->headings();
        $rows = $export->collection();

        $this->assertCount(30, $headings);
        $this->assertCount(2, $rows);
        $this->assertSame(count($headings), count($rows->first()));
        $this->assertSame('Budi Santoso', $rows->first()[0]);
        $this->assertSame('PPPK', $rows->last()[5]);
    }
}
