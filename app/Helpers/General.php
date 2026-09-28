<?php



function uploadImage($folder, $image)
{
    $extension = strtolower($image->getClientOriginalExtension());

    // generate unique name with timestamp + random string
    $filename = uniqid() . '_' . time() . '.' . $extension;

    $image->move(base_path($folder), $filename);

    return $filename;
}



function uploadFile($file, $folder)
{
    $path = $file->store($folder);
    return $path;
}




