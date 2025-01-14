<?php

declare(strict_types=1);

namespace Oscabrera\QueryFilters\Utilities;

use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class QueryFilters
 *
 * Represents the query filters for performing database queries.
 */
class QueryFilters
{
    /**
     * Class constructor.
     *
     * @param array<string|int, string|bool|int|float|array<int|string, int|string|bool|float|Closure>> $conditions
     * An array of conditions to be used in the class.
     *
     * The field to use for sorting.
     */
    public function __construct(
        public array $conditions = [],
    ) {}

    /**
     * Apply the given conditions to the query.
     */
    public function apply(Builder $query): void
    {
        foreach ($this->conditions as $key => $condition) {
            if (is_array($condition)) {
                $this->handleArrayCondition($query, $key, $condition);
                continue;
            }
            $query->where(strval($key), $condition);
        }
    }

    /**
     * Handle the given array condition.
     *
     * @param array<int|string, int|string|bool|float|Closure> $condition
     *      The condition to be applied.
     *      If $key is a string, $condition represents the values to be checked against the column defined by $key.
     *      If $key is an integer, $condition must be an associative array with two elements:
     *      - 'column': represents the column name or column index to apply the condition on.
     *      - 'value': represents the value(s) to be checked against the column defined by 'column'.
     *      If $condition is an array with three or more elements, it must be an associative array with three elements:
     *      - 'column': represents the column name or column index to apply the condition on.
     *      - 'operator': represents the operator to be used in the condition (e.g., '=', '>', 'LIKE').
     *      - 'value': represents the value(s) to be checked against the column defined by 'column'
     *      and using the 'operator'.
     */
    protected function handleArrayCondition(Builder $query, string|int $key, array $condition): void
    {
        if (is_string($key)) {
            if (isset($condition['subQuery']) && ($condition['subQuery'] instanceof Closure)) {
                if (isset($condition['not']) && $condition['not'] === true) {
                    $this->handleSubQueryConditionNot($query, $key, $condition['subQuery']);
                    return;
                }
                $this->handleSubQueryCondition($query, $key, $condition['subQuery']);
                return;
            }
            $query->whereIn($key, $condition);

            return;
        }
        if (count($condition) === 2) {
            $query->where($condition);

            return;
        }

        $this->handleTripleCondition($query, $condition);
    }

    /**
     * Handles a triple condition for a query builder.
     *
     * @param array<int|string, int|string|bool|float|Closure|array<int, string>> $condition
     *                               The triple condition in the format [column, operator, value].
     *                               The operator should be a string comparing operator e.g. '=', '<', '>', etc.
     *                               The value can be either a single value or an array of values for a multiple
     *                               value condition.
     */
    protected function handleTripleCondition(Builder $query, array $condition): void
    {
        if (is_array($condition[2])) {
            /** @var array{0: string, 1: string, 2: array<int, string>} $condition $condition */
            $this->handleArrayValueCondition($query, $condition);

            return;
        }
        /** @var array{0: string, 1: string, 2: int|string|bool|float|array<int, string>} $condition */
        $key = strval($condition[0]);
        $operator = strval($condition[1]);
        $value = $condition[2];
        $query->where($key, $operator, $value);
    }

    /**
     * Handles an array value condition for a query builder.
     *
     * @param array{0: string, 1: string, 2: array<int, string>} $condition
     *                               The triple condition in the format [column, operator, value].
     *                               The operator should be a string comparing operator e.g. '=', '<', '>', etc.
     *                               The value can be either a single value or an array of values for a multiple
     *                               value condition.
     */
    protected function handleArrayValueCondition(Builder $query, array $condition): void
    {
        if ($condition[1] === '<>') {
            $query->whereNotIn(strval($condition[0]), $condition[2]);

            return;
        }
        $query->whereIn(strval($condition[0]), $condition[2]);
    }

    /**
     * Handles a subQuery condition for a query builder.
     */
    protected function handleSubQueryCondition(
        Builder $query,
        string $column,
        Closure $subQuery,
    ): void {
        $query->whereIn($column, $subQuery);
    }

    /**
     * Handles a subQuery condition for a query builder.
     */
    protected function handleSubQueryConditionNot(
        Builder $query,
        string $column,
        Closure $subQuery,
    ): void {
        $query->whereNotIn($column, $subQuery);
    }
}
