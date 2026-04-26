<?php
// site/models/GalleryModel.php

class GalleryModel extends Db
{
    public function forCoworking(int $coworkingId): array
    {
        return $this->all(
            "SELECT * FROM gallery
             WHERE coworking_id = ?
             ORDER BY is_main DESC, id ASC",
            [$coworkingId]
        );
    }
}
