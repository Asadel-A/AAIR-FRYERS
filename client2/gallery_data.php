<?php


header('Content-Type: application/json');

$allImages = [
    [
        'id'      => 1,
        'file'    => 'macengjazz-20.jpg',
        'caption' => 'Alto sax solo — Spring Concert 2026',
        'desc'    => 'Daniel on the alto saxophone during the Spring Concert finale at TSH B128.',
        'tag'     => 'performance',
        'span'    => 'tall',   
        'music'   => 'audio/unforgettable.mp3',     
    ],
    [
        'id'      => 2,
        'file'    => 'macengjazz-15.jpg',
        'caption' => 'Will Zhang conducting',
        'desc'    => 'Director Will Zhang leads the band through a rehearsal set.',
        'tag'     => 'rehearsal',
        'span'    => 'wide',
        'music'   => 'audio/unforgettable.mp3', // replace song placeholder rightnow.
    ],
    [
        'id'      => 3,
        'file'    => 'macengjazz-27.jpg',
        'caption' => 'Full band — Spring Concert 2026',
        'desc'    => 'The full Mac Eng Jazz ensemble on stage. "Mac Eng Jazz — Final Concert Ft. Will Zhang 2026" written on the chalkboard behind them.',
        'tag'     => 'performance',
        'span'    => 'wide',
        'music'   => 'audio/unforgettable.mp3', // replace song placeholder rightnow.
    ],
    [
        'id'      => 4,
        'file'    => 'macengjazz-2.jpg',
        'caption' => 'Eyes on the conductor',
        'desc'    => 'The reed section watches Will intently as he cues the next phrase.',
        'tag'     => 'performance',
        'span'    => 'normal',
        'music'   => 'audio/unforgettable.mp3', // replace song placeholder rightnow.
    ],
    [
        'id'      => 5,
        'file'    => 'macengjazz-26.jpg',
        'caption' => 'Full ensemble — wide shot',
        'desc'    => 'A wide view of the full big band on concert night.',
        'tag'     => 'performance',
        'span'    => 'wide',
        'music'   => 'audio/unforgettable.mp3', // replace song placeholder rightnow.
    ],
    [
        'id'      => 6,
        'file'    => 'macengjazz-48.jpg',
        'caption' => 'Conducting — hands raised',
        'desc'    => 'Will Zhang with both hands raised, driving the climax of the set.',
        'tag'     => 'performance',
        'span'    => 'tall',
        'music'   => 'audio/unforgettable.mp3', // replace song placeholder rightnow.
    ],
    [
        'id'      => 7,
        'file'    => 'macengjazz-57.jpg',
        'caption' => 'Reed section in focus',
        'desc'    => 'Alto and clarinet players deep in concentration during the concert.',
        'tag'     => 'performance',
        'span'    => 'normal',
        'music'   => 'audio/unforgettable.mp3', // replace song placeholder rightnow.
    ],
    [
        'id'      => 8,
        'file'    => 'macengjazz-41.jpg',
        'caption' => 'Packed auditorium',
        'desc'    => 'A sold-out crowd applauds after a memorable performance.',
        'tag'     => 'audience',
        'span'    => 'wide',
        'music'   => 'audio/unforgettable.mp3', // replace song placeholder rightnow.
    ],
    [
        'id'      => 9,
        'file'    => 'macengjazz-56.jpg',
        'caption' => 'Reeds playing hard',
        'desc'    => 'The saxophone section pushes through an energetic arrangement.',
        'tag'     => 'performance',
        'span'    => 'normal',
        'music'   => 'audio/unforgettable.mp3', // replace song placeholder rightnow.
    ],
    [
        'id'      => 10,
        'file'    => 'macengjazz-45.jpg',
        'caption' => 'Rhythm section smiling',
        'desc'    => 'Piano and rhythm players share a laugh between sets.',
        'tag'     => 'candid',
        'span'    => 'normal',
        'music'   => 'audio/unforgettable.mp3', // replace song placeholder rightnow.
    ],
    [
        'id'      => 11,
        'file'    => 'macengjazz-44.jpg',
        'caption' => 'Conducting — second set',
        'desc'    => 'Will Zhang conducts the second half of the Spring Concert.',
        'tag'     => 'performance',
        'span'    => 'tall',
        'music'   => 'audio/unforgettable.mp3', // replace song placeholder rightnow.
    ],
    [
        'id'      => 12,
        'file'    => 'macengjazz-38.jpg',
        'caption' => 'Reeds — wide angle',
        'desc'    => 'A wide-angle view of the saxophone and clarinet section performing.',
        'tag'     => 'performance',
        'span'    => 'wide',
        'music'   => 'audio/unforgettable.mp3', // replace song placeholder rightnow.
    ],
    [
        'id'      => 13,
        'file'    => 'macengjazz-50.jpg',
        'caption' => 'Will smiling after the show',
        'desc'    => 'A proud moment — Will Zhang smiling with the band after a great night.',
        'tag'     => 'candid',
        'span'    => 'normal',
        'music'   => 'audio/unforgettable.mp3', // replace song placeholder rightnow.
    ],
    [
        'id'      => 14,
        'file'    => 'macengjazz-9.jpg',
        'caption' => 'Bari sax takes a bow',
        'desc'    => 'A soloist waves to the crowd after their featured moment.',
        'tag'     => 'candid',
        'span'    => 'normal',
        'music'   => 'audio/unforgettable.mp3', // replace song placeholder rightnow.
    ],
    [
        'id'      => 15,
        'file'    => 'macengjazz-10.jpg',
        'caption' => 'Full band — audience view',
        'desc'    => 'The ensemble as seen from the back of the lecture hall.',
        'tag'     => 'performance',
        'span'    => 'wide',
        'music'   => 'audio/unforgettable.mp3', // replace song placeholder rightnow.
    ],
    [
        'id'      => 16,
        'file'    => 'macengjazz-43.jpg',
        'caption' => 'Conducting — cue moment',
        'desc'    => 'Will cues the brass section for a big entrance.',
        'tag'     => 'performance',
        'span'    => 'normal',
        'music'   => 'audio/unforgettable.mp3',// replace song placeholder rightnow. 
    ],
    [
        'id'      => 17,
        'file'    => 'macengjazz-49.jpg',
        'caption' => 'Vocalist addresses the crowd',
        'desc'    => 'A vocalist speaks to the audience between songs at the Spring Concert.',
        'tag'     => 'candid',
        'span'    => 'tall',
        'music'   => 'audio/unforgettable.mp3', // replace song placeholder rightnow.
    ],
];

$PER_PAGE = 6;

if (isset($_GET['id'])) {
    $id  = (int)$_GET['id'];
    $img = null;
    foreach ($allImages as $i) {
        if ($i['id'] === $id) { $img = $i; break; }
    }
    if (!$img) {
        http_response_code(404);
        echo json_encode(['error' => 'Image not found']);
        exit;
    }
    echo json_encode($img);
    exit;
}

$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $PER_PAGE;
$slice  = array_slice($allImages, $offset, $PER_PAGE);
$total  = count($allImages);

echo json_encode([
    'images'   => $slice,
    'page'     => $page,
    'per_page' => $PER_PAGE,
    'total'    => $total,
    'has_more' => ($offset + $PER_PAGE) < $total,
]);
