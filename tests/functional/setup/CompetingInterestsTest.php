<?php

/**
 * @file tests/functional/setup/CompetingInterestsTest.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CompetingInterestsTest
 * @ingroup tests_functional_setup
 *
 * @brief Test for competing interests setup
 */

import('tests.ContentBaseTestCase');

class CompetingInterestsTest extends ContentBaseTestCase {
	/** @var $fullTitle Full title of test submission */
	static $fullTitle = 'Lost Tracks: Buffalo National Park, 1909-1939';

	/**
	 * @copydoc WebTestCase::getAffectedTables
	 */
	protected function getAffectedTables() {
		return PKP_TEST_ENTIRE_DB;
	}

	/**
	 * Test the system's operations with reviewer CIs enabled.
	 */
	function testCIDisabled() {
		$this->open(self::$baseUrl);

		// Unset the CI requirement setting
		$this->logIn('dbarnes');
		$this->_setReviewerCIRequirement(false);
		$this->logOut();

		// Send the submission to review
		$this->findSubmissionAsEditor('dbarnes', null, self::$fullTitle);
		$this->sendToReview('External');
		$this->assignReviewer('agallego', 'Adela Gallego');
		$this->logOut();

		// Submit review with no competing interests
		$this->logIn('agallego');
		$this->scrollPageDown();
		$xpath = '//span[contains(text(),' . $this->quoteXpath(self::$fullTitle) .')]/../../..//a[contains(@id, "-stage-itemWorkflow-button-")]';
		$this->waitForElementPresent($xpath);
		$this->clickAndWait($xpath);

		$this->waitForElementPresent('id=reviewStep1Form');
		$this->assertElementNotPresent('//label[@for=\'noCompetingInterests\']');
		$this->clickLinkActionNamed('Accept Review, Continue to Step #2');
		$this->clickLinkActionNamed('Continue to Step #3');
		$this->waitJQuery();
		$this->typeTinyMCE('comments', 'This paper is suitable for publication.');
		$this->clickLinkActionNamed('Submit Review');
		$this->waitForElementPresent($selector='link=OK');
		$this->click($selector);
		$this->waitForElementPresent('//h2[contains(text(), \'Review Submitted\')]');

		$this->logOut();

		// Find and view the review
		$this->findSubmissionAsEditor('dbarnes', null, self::$fullTitle);
		$this->waitForElementPresent($selector='//span[contains(text(), \'Adela Gallego\')]/../../..//a[@title=\'Read this review\']');
		$this->click($selector);

		// There should not be a visible CI statement.
		$this->waitForElementPresent('//h3[text()=\'Reviewer Comments\']');
		$this->assertElementNotPresent('//h3[text()=\'Competing Interests\']');
		$this->logOut();
	}

	/**
	 * Test the system's operations with reviewer CIs enabled.
	 */
	function testCIRequired() {
		$this->open(self::$baseUrl);

		// Set the CI requirement setting
		$this->logIn('dbarnes');
		$this->_setReviewerCIRequirement(true);
		$this->logOut();

		// Send the submission to review
		$this->findSubmissionAsEditor('dbarnes', null, self::$fullTitle);
		$this->sendToReview('External');
		$this->assignReviewer('alzacharia', 'Al Zacharia');
		$this->logOut();

		// Submit review with competing interests
		$competingInterests = 'I work for a competing company';
		$this->logIn('alzacharia');
		$this->scrollPageDown();
		$xpath = '//span[contains(text(),' . $this->quoteXpath(self::$fullTitle) .')]/../../..//a[contains(@id, "-stage-itemWorkflow-button-")]';
		$this->waitForElementPresent($xpath);
		$this->clickAndWait($xpath);

		$this->waitForElementPresent('id=reviewStep1Form');
		$this->assertElementPresent($selector='//input[@id=\'hasCompetingInterests\']');
		$this->click($selector);
		$this->typeTinyMCE('competingInterestsText', $competingInterests);
		$this->clickLinkActionNamed('Accept Review, Continue to Step #2');
		$this->clickLinkActionNamed('Continue to Step #3');
		$this->waitJQuery();
		$this->typeTinyMCE('comments', 'This paper is suitable for publication.');
		$this->clickLinkActionNamed('Submit Review');
		$this->waitForElementPresent($selector='link=OK');
		$this->click($selector);
		$this->waitForElementPresent('//h2[contains(text(), \'Review Submitted\')]');

		$this->logOut();

		// Find and view the review
		$this->findSubmissionAsEditor('dbarnes', null, self::$fullTitle);
		$this->waitForElementPresent($selector='//span[contains(text(), \'Al Zacharia\')]/../../..//a[@title=\'Read this review\']');
		$this->click($selector);

		// There should be a visible CI statement.
		$this->waitForElementPresent('//h3[text()=\'Reviewer Comments\']');
		$this->assertElementPresent('//*[contains(.,\'' . $competingInterests . '\')]');

		// Disable the CI requirement again
		$this->_setReviewerCIRequirement(true);
		$this->logOut();

		// The CI statement entered previously should still be visible.
		$this->findSubmissionAsEditor('dbarnes', null, self::$fullTitle);
		$this->waitForElementPresent($selector='//span[contains(text(), \'Al Zacharia\')]/../../..//a[@title=\'Read this review\']');
		$this->click($selector);
		$this->waitForElementPresent('//h3[text()=\'Reviewer Comments\']');
		$this->assertElementPresent('//*[contains(.,\'' . $competingInterests . '\')]');

		// Finished.
		$this->logOut();
	}

	/**
	 * Set the reviewer's competing interests requirement state.
	 * @param $state boolean True to require a CI statement.
	 */
	private function _setReviewerCIRequirement($state) {
		$this->clickAndWait('link=Workflow');
		$this->waitForElementPresent('link=Review');
		$this->click('link=Review');
		$selector = 'id=reviewerCompetingInterestsRequired';
		$this->waitForElementPresent($selector);
		if ($state) {
			$this->check($selector);
		} else {
			$this->uncheck($selector);
		}
		$this->click('//form[@id=\'reviewStageForm\']//button[text()=\'Save\']');
		$this->waitForElementPresent('//*[contains(.,\'Your changes have been saved.\')]');

	}
}
