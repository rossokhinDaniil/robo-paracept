<?php

declare(strict_types=1);

namespace Codeception\Task\Filter;

use Codeception\Test\Descriptor;
use Codeception\TestInterface;
use boxblinkracer\CodeceptionTestRail\Services\TestRailAPIClient;

/**
 * Class DefaultFilter - The Default Filter which is implemented by default
 */
class TestRailFilter implements Filter
{
    private array $tests = [];

    private const ANNOTATION_CASE = 'case';
    /**
     * @inheritDoc
     */
    public function setTests(array $tests): void
    {
        $this->tests = $tests;
    }

    /**
     * @inheritDoc
     */
    public function filter(): array
    {
        $client = new TestRailAPIClient(
            $_ENV["TESTRAIL_URL"],
            $_ENV["TESTRAIL_USER"],
            $_ENV["TESTRAIL_PASSWORD"],
        );

        $testRunCaseId = [];
        $testPath = [];

        $response = $client->getRunCase($_ENV["RUN_ID"]);
        foreach ($response as $test){
            array_push($testRunCaseId, $test->case_id);
        }

        $allTestsSuite = $this->tests;
        foreach ($allTestsSuite as $test) {
            $testCaseId = $this->getTestCaseAnnotations($test);
            if ($testCaseId) {
                foreach ($testRunCaseId as $caseId) {
                    if ($caseId == (int)$testCaseId[0]) {
                        array_push($testPath, $test);
                    }
                }
            }
        }

        return $testPath;
    }

    private function getTestCaseAnnotations($test){
        $filename = Descriptor::getTestFileName($test);
        $info = $test->getReportFields();
        $annotations = \PHPUnit\Util\Test::parseTestMethodAnnotations($info['class'], $info['name']);
        if (isset($annotations['method']['case'])) {
            $caseID = $annotations['method']['case'][0];
            $caseID = str_replace('C', '', $caseID);
            return $caseID;
        }
    }
}
