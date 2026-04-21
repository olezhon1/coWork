<?php
// site/models/GalleryModel.php

class GalleryModel extends Db
{
    public function forCoworking(int $coworkingId): array
    {
        return $this->all(
            "SELECT * FROM gallery
             WHERE entity_type = 'coworking' AND entity_id = ?
             ORDER BY is_main DESC, id ASC",
            [$coworkingId]
        );
    }

    public function forWorkspace(int $workspaceId): array
    {
        return $this->all(
            "SELECT * FROM gallery
             WHERE entity_type = 'workspace' AND entity_id = ?
             ORDER BY is_main DESC, id ASC",
            [$workspaceId]
        );
    }
}
