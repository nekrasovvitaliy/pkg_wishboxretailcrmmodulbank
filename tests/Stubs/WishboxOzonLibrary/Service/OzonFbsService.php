<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace Tests\Stubs\WishboxOzonLibrary\Service;

use stdClass;

/**
 * Supplies deterministic Ozon FBS postings without making network requests.
 *
 * @since 1.0.0
 */
final class OzonFbsService
{
	/** @var array<string, mixed> */
	public array $lastFilter = [];

	public int $requestCount = 0;

	/**
	 * @param   stdClass  $response  Simulated Ozon response.
	 *
	 * @since 1.0.0
	 */
	public function __construct(private readonly stdClass $response)
	{
	}

	/**
	 * @param   array<string, mixed>  $filter  Ozon posting filters.
	 * @param   array<string, bool>   $with    Optional response sections.
	 *
	 * @since 1.0.0
	 */
	public function getPostings(
		array $filter,
		int $limit = 1000,
		string $cursor = '',
		string $direction = 'ASC',
		array $with = []
	): stdClass {
		$this->lastFilter = $filter;
		$this->requestCount++;

		return $this->response;
	}
}
