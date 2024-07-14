<?php
$page = isset($_GET['page']) ? $_GET['page'] : 'home';
$action = isset($_GET['act']) ? $_GET['act'] : 'default';

// $list_menu = array(
//   array(
//     'url' => '?page=home',
//     'icon' => '<i class="text-[1.1rem] text-[#0e69ff] bx bx-home"></i>',
//     'label' => 'Home'
//   ),
//   array(
//     'url' => '?page=data-siswa',
//     'icon' => '<i class="text-[1.1rem] text-[#0e69ff] bx bx-group"></i>',
//     'label' => 'Data Siswa'
//   ),
//   array(
//     'url' => '?page=data-kriteria',
//     'icon' => '<i class="text-[1.1rem] text-[#0e69ff] bx bx-spreadsheet"></i>',
//     'label' => 'Data Kriteria'
//   ),
//   array(
//     'url' => '?page=perhitungan',
//     'icon' => '<i class="text-[1.1rem] text-[#0e69ff] bx bx-loader-circle"></i>',
//     'label' => 'Perhitungan'
//   ),
//   array(
//     'url' => '#',
//     'icon' => '<i class="text-[1.1rem] text-[#0e69ff] bx bx-exit"></i>',
//     'label' => 'Logout'
//   )
// );

$pages = array(
  'home' => 'components/form-data.php',
  'view-upload' => 'components/view-upload.php',
  'uploaded-file' => 'components/uploaded-file.php',
);
