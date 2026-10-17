<?php

namespace Webkul\Category\Helpers;

use DomainException;

class CategoryTreeOrder
{
    /**
     * Identify one exact hierarchy and sibling order.
     */
    public static function revision(iterable $categories): string
    {
        $rows = [];

        foreach ($categories as $category) {
            $rows[(int) $category->id] = [(int) $category->id, $category->parent_id === null ? null : (int) $category->parent_id, (int) $category->position];
        }

        ksort($rows);

        return hash('sha256', json_encode(array_values($rows), JSON_THROW_ON_ERROR));
    }

    /**
     * Validate a move and calculate complete orders for both affected parents.
     */
    public static function plan(iterable $categories, int $id, int $parentId, ?int $beforeId, array $protectedIds): array
    {
        $rows = [];

        foreach ($categories as $category) {
            $rows[(int) $category->id] = $category;
        }

        if (! isset($rows[$id], $rows[$parentId]) || $beforeId === $id) {
            throw new DomainException('invalid');
        }

        $path = static function (int $nodeId) use ($rows): array {
            $ancestors = [];

            while (isset($rows[$nodeId])) {
                if (in_array($nodeId, $ancestors, true)) {
                    throw new DomainException('cycle');
                }

                $ancestors[] = $nodeId;
                $parent = $rows[$nodeId]->parent_id;

                if ($parent === null) {
                    return $ancestors;
                }

                $nodeId = (int) $parent;
            }

            throw new DomainException('invalid');
        };

        $sourcePath = $path($id);
        $targetPath = $path($parentId);
        $protectedIds = array_unique(array_merge([1], array_map('intval', $protectedIds)));

        if ($rows[$id]->parent_id === null || in_array($id, $protectedIds, true)) {
            throw new DomainException('root');
        }

        foreach ($protectedIds as $protectedId) {
            if (isset($rows[$protectedId]) && in_array($id, $path($protectedId), true)) {
                throw new DomainException('root');
            }
        }

        if (in_array($id, $targetPath, true)) {
            throw new DomainException('cycle');
        }

        $scope = static fn (array $ancestors) => array_values(array_intersect($ancestors, $protectedIds))[0] ?? end($ancestors);

        if ($scope($sourcePath) !== $scope($targetPath)) {
            throw new DomainException('scope');
        }

        if ($beforeId !== null && (! isset($rows[$beforeId]) || (int) $rows[$beforeId]->parent_id !== $parentId)) {
            throw new DomainException('invalid');
        }

        $siblings = static function (int $parent) use ($rows, $id): array {
            $list = array_filter($rows, static fn ($row) => $row->parent_id !== null && (int) $row->parent_id === $parent && (int) $row->id !== $id);
            usort($list, static fn ($a, $b) => [(int) $a->position, (int) $a->id] <=> [(int) $b->position, (int) $b->id]);

            return array_map(static fn ($row) => (int) $row->id, $list);
        };

        $oldParent = (int) $rows[$id]->parent_id;
        $newOrder = $siblings($parentId);
        $offset = $beforeId === null ? count($newOrder) : array_search($beforeId, $newOrder, true);
        array_splice($newOrder, $offset, 0, [$id]);
        $orders = [$parentId => $newOrder];

        if ($oldParent !== $parentId) {
            $orders[$oldParent] = $siblings($oldParent);
        }

        return $orders;
    }
}
