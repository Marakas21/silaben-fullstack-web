<?php  
class Home_model{
	private $db;

	public function __construct(){
		// create object from database class
		$this->db = new Database;

		// check status
		if($this->db == false){
			//echo "<script>console.log('Connection failed.' );</script>";
		}else{
			//echo "<script>console.log('Connected successfully.' );</script>";
		}
	}

	// Generate unique id
	private function generate_unique_id() {
        return uniqid();
    }

	// Fungsi untuk memperbarui lokasi masyarakat di database
    public function updateUserLocation($user_id, $latitude, $longitude) {
        $updated_at = date('Y-m-d H:i:s');
        $sql = "UPDATE tbl_user 
                SET latitude = '$latitude', longitude = '$longitude'
                WHERE user_id = '$user_id'";

        return $this->db->query($sql); // Gunakan method query() dari kelas Database
    }

    // Fungsi untuk mendapatkan bencana yang berdekatan dengan lokasi pengguna
    public function getNearbyDisasters($userLat, $userLng) {
		$geofenceRadius = 8; // 8 km radius (6371 adalah radius bumi dalam km)
	
		// Pastikan nilai latitude dan longitude valid
		if (empty($userLat) || empty($userLng)) {
			return [];
		}
	
		// Haversine formula untuk menghitung jarak berdasarkan latitude dan longitude
		$sql = "SELECT *, 
					   (6371 * acos(cos(radians($userLat)) * cos(radians(latitude)) 
					   * cos(radians(longitude) - radians($userLng)) + sin(radians($userLat)) 
					   * sin(radians(latitude)))) AS distance 
				FROM tbl_laporan
				HAVING distance <= $geofenceRadius";
	
		$result = $this->db->query($sql);
		$nearbyDisasters = [];
	
		// Proses hasil query
		if ($result && $result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				$nearbyDisasters[] = $row;
			}
		}
	
		return $nearbyDisasters;
	}
	

    // Fungsi untuk mendapatkan nomor WhatsApp pengguna
    public function getUserWhatsAppNumber($user_id) {
        $sql = "SELECT whatsapp_number FROM tbl_user WHERE user_id = '$user_id'";
        $result = $this->db->query($sql); // Jalankan query

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['whatsapp_number'];
        }

        return null; // Return null jika tidak ada hasil
    }

	// Fungsi untuk mendapatkan nomor WhatsApp pengguna dalam rentang geografis tertentu
	public function getNearbyUsersWhatsAppNumbers($disasterLat, $disasterLng, $radius = 10) {
		// Formula Haversine untuk menghitung jarak antar dua titik berdasarkan koordinat
		$sql = "
			SELECT 
				whatsapp_number, 
				(
					6371 * ACOS(
						COS(RADIANS($disasterLat)) * 
						COS(RADIANS(latitude)) * 
						COS(RADIANS(longitude) - RADIANS($disasterLng)) + 
						SIN(RADIANS($disasterLat)) * 
						SIN(RADIANS(latitude))
					)
				) AS distance 
			FROM tbl_user 
			HAVING distance <= $radius
		";
	
		$result = $this->db->query($sql); // Jalankan query
	
		$whatsappNumbers = [];
		if ($result->num_rows > 0) {
			// Ambil semua nomor WhatsApp pengguna dalam radius tertentu
			while ($row = $result->fetch_assoc()) {
				$whatsappNumbers[] = $row['whatsapp_number'];
			}
		}
	
		return $whatsappNumbers; // Return array nomor WhatsApp
	}
	



	public function get_total_reports() {
		$result = $this->db->query("
			SELECT COUNT(`pelapor_id`) AS total_report 
			FROM tbl_laporan;
		");
		// var_dump(($result));
		
		if ($result && $result->num_rows > 0) {
			$row = $result->fetch_assoc();
			return $row['total_report'];
		} else {
			return 0; // Jika tidak ada hasil, kembalikan 0
		}

		$this->db->db_close();
	}

	// Get status laporan untuk dashboard
	public function get_reports_by_status($user_id) {
		$result = $this->db->query("
			SELECT 
				SUM(status = 'verified') AS verified,
				SUM(status = 'unverified') AS unverified
			FROM tbl_laporan;
		");
		return $result->fetch_assoc();
	}

	// Get Laporan yang paling sering untuk dashboard
	public function get_all_categories() {
		//$sql = "SELECT jenis_bencana, COUNT(*) as jumlah FROM tbl_laporan GROUP BY jenis_bencana";
		$result = $this->db->query("
			SELECT jenis_bencana, COUNT(*) as jumlah FROM tbl_laporan GROUP BY jenis_bencana
		");

		
		//Menyimpan data dalam array
		$all_categories = [];
		while ($row = $result->fetch_assoc()) {
			$all_categories[] = [
				'jenis_bencana' => $row['jenis_bencana'],
				'count' => $row['jumlah']
			];
		}

		$this->db->db_close();
	}

	// Get tren laporan untuk dashboard
	public function get_report_trends($user_id, $interval) {
		$result = $this->db->query("
			SELECT COUNT(*) as report_count, DATE_FORMAT(report_date, '%Y-%m-%d') as report_day 
			FROM tbl_laporan 
			GROUP BY report_day 
			ORDER BY report_day;
		");
		return $result->fetch_all(MYSQLI_ASSOC);
	}

	// Ambil semua nomor relawan dari database
	public function get_all_volunteer_numbers() {
		$query = "SELECT no_whatsapp FROM tbl_relawan";
		$result = $this->db->query($query);

		$numbers = [];
		while ($row = $result->fetch_assoc()) {
			$numbers[] = $row['no_whatsapp'];
		}
		return $numbers;
		var_dump($numbers);
	}

	// Ambil semua nomor masyarakat dari database
	public function get_all_public_numbers() {
		$query = "SELECT whatsapp_number FROM tbl_user";
		$result = $this->db->query($query);

		$numbers = [];
		while ($row = $result->fetch_assoc()) {
			$numbers[] = $row['whatsapp_number'];
		}
		return $numbers;
		var_dump($numbers);
	}

	// Ambil data pelaporan terbaru
	public function get_latest_report() {
		$query = "SELECT * FROM tbl_laporan ORDER BY laporan_id DESC LIMIT 1";
		$result = $this->db->query($query);
		return $result->fetch_assoc();
	}

	// Mendapatkan laporan yang belum dinotifikasi
    public function get_unnotified_reports() {
        // Query untuk mendapatkan laporan yang belum dinotifikasi
        $sql = "SELECT * FROM tbl_laporan WHERE is_notified = 0";

        // Eksekusi query
        $result = $this->db->query($sql);

        // Cek jika query gagal
        // if (!$result) {
        //     echo "Error in query: " . $this->db->error; // Ubah menjadi $this->db->connect_error jika perlu
        //     return array();
        // }

        // Tampilkan hasil query untuk memastikan hasilnya array
        $reports = array();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $reports[] = $row;  // Simpan setiap baris hasil query ke dalam array
            }
        }

        return $reports; // Mengembalikan array laporan yang belum dinotifikasi
    }

	public function get_all_users_with_coordinates() {
		// Query untuk mendapatkan semua pengguna dengan nomor telepon, latitude, dan longitude
		$sql = "SELECT user_id, whatsapp_number, latitude, longitude 
				FROM tbl_user";
		
		// Eksekusi query
		$query = $this->db->query($sql);

		return $query->fetch_assoc();
	}
	
	

	// Simpan data notifikasi ke tabel notifikasi
	public function save_notification($data) {
		$this->db->query("
			INSERT INTO notifications (laporan_id, user_id, status, message, created_at, updated_at)
			VALUES (?, ?, ?, ?, ?, ?)
		", array(
			$data['laporan_id'],
			$data['user_id'],
			$data['status'],
			$data['message'],
			$data['created_at'],
			$data['updated_at']
		));
	}

	// Update laporan untuk menandai bahwa notifikasi sudah dikirim
	public function mark_report_as_notified($laporan_id) {
		// Query untuk memperbarui laporan sebagai sudah dinotifikasi
		$sql = "UPDATE tbl_laporan SET is_notified = 1";
		
		// Eksekusi query dengan bind parameter untuk keamanan
		return $this->db->query($sql, array($laporan_id));
	}

	public function mark_report_as_notified_relawan($laporan_id) {
		// Query untuk memperbarui laporan sebagai sudah dinotifikasi
		$sql = "UPDATE tbl_laporan SET is_notified_relawan = 1";
		
		// Eksekusi query dengan bind parameter untuk keamanan
		return $this->db->query($sql, array($laporan_id));
	}

	public function mark_report_as_notified_masyarakat($laporan_id) {
		// Query untuk memperbarui laporan sebagai sudah dinotifikasi
		$sql = "UPDATE tbl_laporan SET is_notified_masyarakat = 1";
		
		// Eksekusi query dengan bind parameter untuk keamanan
		return $this->db->query($sql, array($laporan_id));
	}
	

	// Get total lapora untuk dashboard
	// Menggunakan MySQLi
	// Home_model.php
	
	// insert data pelaporan
	public function insert_data_pelaporan($data){ 
		$laporan_id = $this->generate_unique_id();
		$deskripsi = $data['deskripsi'];
		$latitude = $data['latitude'];
		$longtitude = $data['longtitude'];
		$image = $data['image'];
		$status = $data['status'];

		try{
			// case sensitive dengan menambahkan modifier BINARY sebelum kolom name
			$result = $this->db->query("INSERT INTO `tbl_laporan`(`laporan_id`, `deskripsi`, `latitude`, `longtitude`, `image`, `status`) 
										VALUES ('$laporan_id','$deskripsi','$latitude','$longtitude','user','$image','$status');");
			$this->db->db_close(); // Close database connection
			
			return $result; 
		} catch (Exception $e) {
			echo "Maaf terjadi kesalahan: " . $e->getMessage();
		}
	}

	
	// insert data pelaporan dari web
	public function insert_data_pelaporan_web($id_laporan, $file_name, $data, $data_ai){ 
		session_start();
		$user_id = $_SESSION['user_id'];
		$user_role = $_SESSION['role'];

		// Validate and sanitize user input
		$reportTitle = filter_input(INPUT_POST, 'report-title', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$reportDescription = filter_input(INPUT_POST, 'report-description', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$longitude = filter_input(INPUT_POST, 'input-long-location', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$latitude = filter_input(INPUT_POST, 'input-lat-location', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$location = filter_input(INPUT_POST, 'input-lokasi-bencana', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$agency = filter_input(INPUT_POST, 'lapor-instansi', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$reportDate = filter_input(INPUT_POST, 'report-date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$reportTime = filter_input(INPUT_POST, 'report-time', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		//$identity = filter_input(INPUT_POST, 'identity', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		
		// data AI
		$jenis_bencana = $data_ai['jenis_bencana'];
		$klasifikasi_bencana = $data_ai['klasifikasi_bencana'];
		$level_kerusakan_infrastruktur = $data_ai['level_kerusakan_infrastruktur'];
		$level_bencana = $data_ai['level_bencana'];
		$kesesuaian_laporan = $data_ai['kesesuaian_laporan'];
		$deskripsi_singkat_ai = $data_ai['deskripsi_singkat_ai'];
		$saran_singkat = $data_ai['saran_singkat'];
		$potensi_bahaya_lanjutan = $data_ai['potensi_bahaya_lanjutan'];
		$penilaian_akibat_bencana = $data_ai['penilaian_akibat_bencana'];
		$kondisi_cuaca = $data_ai['kondisi_cuaca'];
		$hubungi_instansi_terkait = $data_ai['hubungi_instansi_terkait'];
		
		// Cek status kesesuai laporan
		if($kesesuaian_laporan === "sesuai"){
			$isVerified = "verified";
		}else{
			$isVerified = "unverified";
		}

		try{
			// case sensitive dengan menambahkan modifier BINARY sebelum kolom name
			$result = $this->db->query("INSERT INTO `tbl_laporan`(`laporan_id`, `pelapor_id`, `pelapor_role`, `report_title`, `report_description`, `latitude`, `longitude`, `lokasi_bencana`, `lapor_instansi`, `report_date`, `report_time`, `report_file_name_bukti`, `identity`, `status`, `jenis_bencana`, `klasifikasi_bencana`, `level_kerusakan_infrastruktur`, `level_bencana`, `kesesuaian_laporan`, `deskripsi_singkat_ai`, `saran_singkat`, `potensi_bahaya_lanjutan`, `penilaian_akibat_bencana`, `kondisi_cuaca`, `hubungi_instansi_terkait`) VALUES ('$id_laporan','$user_id','$user_role','$reportTitle','$reportDescription','$latitude','$longitude','$location','$agency','$reportDate','$reportTime','$file_name','Not Anonymous','$isVerified', '$jenis_bencana', '$klasifikasi_bencana', '$level_kerusakan_infrastruktur', '$level_bencana', '$kesesuaian_laporan', '$deskripsi_singkat_ai', '$saran_singkat', '$potensi_bahaya_lanjutan', '$penilaian_akibat_bencana', '$kondisi_cuaca', '$hubungi_instansi_terkait');");
			$this->db->db_close(); // Close database connection
			
			return true; 
		} catch (Exception $e) {
			//echo "Maaf terjadi kesalahan: " . $e->getMessage();
			return false;
		}
	}

	// insert data pelaporan dari mobile
	public function insert_data_pelaporan_mobile($id_laporan, $file_name, $data, $data_ai){ 
		// Validate and sanitize user input
		$reportTitle = filter_input(INPUT_POST, 'report-title', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$reportDescription = filter_input(INPUT_POST, 'report-description', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$longitude = filter_input(INPUT_POST, 'input-long-location', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$latitude = filter_input(INPUT_POST, 'input-lat-location', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$location = filter_input(INPUT_POST, 'input-lokasi-bencana', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$agency = filter_input(INPUT_POST, 'lapor-instansi', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$reportDate = filter_input(INPUT_POST, 'report-date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$reportTime = filter_input(INPUT_POST, 'report-time', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$identity = filter_input(INPUT_POST, 'identity', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$user_id = filter_input(INPUT_POST, 'user-id', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$user_role = filter_input(INPUT_POST, 'user-role', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

		// data AI
		$jenis_bencana = $data_ai['jenis_bencana'];
		$klasifikasi_bencana = $data_ai['klasifikasi_bencana'];
		$level_kerusakan_infrastruktur = $data_ai['level_kerusakan_infrastruktur'];
		$level_bencana = $data_ai['level_bencana'];
		$kesesuaian_laporan = $data_ai['kesesuaian_laporan'];
		$deskripsi_singkat_ai = $data_ai['deskripsi_singkat_ai'];
		$saran_singkat = $data_ai['saran_singkat'];
		$potensi_bahaya_lanjutan = $data_ai['potensi_bahaya_lanjutan'];
		$penilaian_akibat_bencana = $data_ai['penilaian_akibat_bencana'];
		$kondisi_cuaca = $data_ai['kondisi_cuaca'];
		$hubungi_instansi_terkait = $data_ai['hubungi_instansi_terkait'];

		// Cek status kesesuai laporan
		if($kesesuaian_laporan === "sesuai"){
			$isVerified = "verified";
		}else{
			$isVerified = "unverified";
		}
		
		try{
			// case sensitive dengan menambahkan modifier BINARY sebelum kolom name
			$result = $this->db->query("INSERT INTO `tbl_laporan`(`laporan_id`, `pelapor_id`, `pelapor_role`, `report_title`, `report_description`, `latitude`, `longitude`, `lokasi_bencana`, `lapor_instansi`, `report_date`, `report_time`, `report_file_name_bukti`, `identity`, `status`, `jenis_bencana`, `klasifikasi_bencana`, `level_kerusakan_infrastruktur`, `level_bencana`, `kesesuaian_laporan`, `deskripsi_singkat_ai`, `saran_singkat`, `potensi_bahaya_lanjutan`, `penilaian_akibat_bencana`, `kondisi_cuaca`, `hubungi_instansi_terkait`) VALUES ('$id_laporan','$user_id','$user_role','$reportTitle','$reportDescription','$latitude','$longitude','$location','$agency','$reportDate','$reportTime','$file_name','$identity','$isVerified', '$jenis_bencana', '$klasifikasi_bencana', '$level_kerusakan_infrastruktur', '$level_bencana', '$kesesuaian_laporan', '$deskripsi_singkat_ai', '$saran_singkat', '$potensi_bahaya_lanjutan', '$penilaian_akibat_bencana', '$kondisi_cuaca', '$hubungi_instansi_terkait');");
			$this->db->db_close(); // Close database connection
			
			return true; 
		} catch (Exception $e) {
			//echo "Maaf terjadi kesalahan: " . $e->getMessage();
			return false;
		}
	}


	// tampilkan semua data pelaporan
	public function get_data_pelaporan(){
		$result = $this->db->query("select * from tbl_laporan;");
		$this->db->db_close(); // Close database connection
		
		if ($result->num_rows > 0) {
			// konversi hasil query menjadi array asosiatif
			$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
			
			// balik urutan baris
			$rows = array_reverse($rows);

			return $rows;
		} else {
			return []; // Empty array
		}
	}

	// tampilkan semua data pelaporan
	public function get_data_pelaporan_web($user_id){
		$result = $this->db->query("select * from tbl_laporan where pelapor_id = '$user_id';");
		$this->db->db_close(); // Close database connection
		
		if ($result->num_rows > 0) {
			// konversi hasil query menjadi array asosiatif
			$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
			
			// balik urutan baris
			$rows = array_reverse($rows);

			return $rows;
		} else {
			return []; // Empty array
		}
	}

	// tampilkan semua data pelaporan admin
	public function get_data_pelaporan_web_admin($user_id){
		$result = $this->db->query("select * from tbl_laporan;");
		$this->db->db_close(); // Close database connection
		
		if ($result->num_rows > 0) {
			// konversi hasil query menjadi array asosiatif
			$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
			
			// balik urutan baris
			$rows = array_reverse($rows);

			return $rows;
		} else {
			return []; // Empty array
		}
	}

	// tampilkan semua data pelaporan lembaga
	public function get_data_pelaporan_web_lembaga($user_id){
		// Ambil nama_instansi dari session
		$nama_instansi = $_SESSION['user_name'];

		// Pastikan untuk melakukan escape pada input untuk menghindari SQL Injection
		//$nama_instansi = $this->db->real_escape_string($nama_instansi);
		
		$result = $this->db->query("SELECT * FROM tbl_laporan WHERE lapor_instansi = '$nama_instansi';");
		$this->db->db_close(); // Close database connection
		
		if ($result->num_rows > 0) {
			// konversi hasil query menjadi array asosiatif
			$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
			
			// balik urutan baris
			$rows = array_reverse($rows);

			return $rows;
		} else {
			return []; // Empty array
		}
	}

	// tampilkan semua data pelaporan
	public function show_data_pelaporan_map(){
		$result = $this->db->query("select * from tbl_laporan where status != 'unverified';");
		$this->db->db_close(); // Close database connection
		
		if ($result->num_rows > 0) {
			// konversi hasil query menjadi array asosiatif
			$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
			
			// balik urutan baris
			$rows = array_reverse($rows);

			return $rows;
		} else {
			return []; // Empty array
		}
	}

	public function get_data_relawan(){
		$result = $this->db->query(("select * from tbl_relawan"));
		$this->db->db_close();

		if ($result->num_rows > 0) {
			
			$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

			$rows = array_reverse($rows);

			return $rows;
		} else {
			return [];
		}
	}

	public function get_data_pengguna(){
		$result = $this->db->query(("select * from tbl_user"));
		$this->db->db_close();

		if ($result->num_rows > 0) {
			
			$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

			$rows = array_reverse($rows);

			return $rows;
		} else {
			return [];
		}
		
	}
	// Mendapatkan semua deskripsi pengguna
	public function tampilkan_user_name_pengguna() {
		// Query untuk mengambil semua deskripsi dari tabel tbl_user
		$query = "SELECT user_name FROM tbl_user";

		// Jalankan query
		$result = $this->db->query($query);
		
		// Tutup koneksi database
		$this->db->db_close();

		// Periksa apakah ada hasil
		if ($result->num_rows > 0) {
			// Ambil hasil sebagai array asosiatif
			$user_name = [];
			while ($row = mysqli_fetch_assoc($result)) {
				// Tambahkan deskripsi ke dalam array
				$user_name[] = $row['Name'];
			}

			// Kembalikan daftar deskripsi
			return $user_name;
		} else {
			// Jika tidak ada deskripsi, kembalikan array kosong
			return [];
		}
	}

	/*
	

	// get total data
	public function get_summary_data($email_dosen, $role) {
		// total active class dari tbl_classes
		$activeClassesResult = $this->db->query("SELECT COUNT(*) as total_active_classes FROM tbl_classes WHERE status_class = 'active';");
		$activeClassesCount = $activeClassesResult->fetch_assoc()['total_active_classes'];

		// total archived class dari tbl_classes
		$archivedClassesResult = $this->db->query("SELECT COUNT(*) as total_archived_classes FROM tbl_classes WHERE status_class = 'complete';");
		$archivedClassesCount = $archivedClassesResult->fetch_assoc()['total_archived_classes'];

		// total mahasiswa dari tbl_students
		$totalStudentsResult = $this->db->query("SELECT COUNT(*) as total_students FROM tbl_students");
		$totalStudentsCount = $totalStudentsResult->fetch_assoc()['total_students'];

		// total lecturers dari tbl_operator dengan role = 'dosen'
		$totalLecturersResult = $this->db->query("SELECT COUNT(*) as total_lecturers FROM tbl_operator WHERE role = 'dosen';");
		$totalLecturersCount = $totalLecturersResult->fetch_assoc()['total_lecturers'];

		// Buat kosong untuk role yang lain.
		$role_query="";
		
		if($role == "dosen"){
			$role_query="AND email_lecturer = '$email_dosen'"; // jika dosen harus ambil sesuai data
		}

		// Student Attendance History
		$studentAttendanceResult = $this->db->query("SELECT COUNT(*) as total_attendance_history FROM tbl_attendance_history WHERE status IN ('P', 'L', 'E') $role_query;");
		$studentAttendanceCount = $studentAttendanceResult->fetch_assoc()['total_attendance_history'];

		// Student Attendance History (Present)
		$studentAttendanceResult = $this->db->query("SELECT COUNT(*) as total_present FROM tbl_attendance_history WHERE status IN ('P') $role_query;");
		$studentPresent = $studentAttendanceResult->fetch_assoc()['total_present'];

		// Student Attendance History (Late)
		$studentAttendanceResult = $this->db->query("SELECT COUNT(*) as total_late FROM tbl_attendance_history WHERE status IN ('L') $role_query;");
		$studentLate = $studentAttendanceResult->fetch_assoc()['total_late'];

		// Student Attendance History (Izin)
		$studentAttendanceResult = $this->db->query("SELECT COUNT(*) as total_izin FROM tbl_attendance_history WHERE status IN ('E') $role_query;");
		$studentIzin = $studentAttendanceResult->fetch_assoc()['total_izin'];

		$this->db->db_close(); // Tutup koneksi database

		// Mengembalikan data dalam bentuk array asosiatif
		return [
			'total_active_classes' => $activeClassesCount,
			'total_archived_classes' => $archivedClassesCount,
			'total_students' => $totalStudentsCount,
			'total_lecturers' => $totalLecturersCount,
			'total_history_attendance' => $studentAttendanceCount,
			'total_present' => $studentPresent,
			'total_late' => $studentLate,
			'total_izin' => $studentIzin,
		];
	}


	// check operator 
	public function check_operator($data)
	{
		$email = $data['email'];
		
		// case sensitive dengan menambahkan modifier BINARY sebelum kolom name
		$result = $this->db->query("select * from tbl_operator where  email = '$email';");
		$this->db->db_close(); // Close database connection

		if ($result->num_rows > 0) {
			// convert to associative array
			$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
			return $rows;
		} else {
			return []; // kosong return false
		}
	}
	

	// tampilkan semua class dengan status active
	public function get_active_classes(){
		$result = $this->db->query("select * from tbl_classes where status_class = 'active';");
		$this->db->db_close(); // Close database connection
		
		if ($result->num_rows > 0) {
			// konversi hasil query menjadi array asosiatif
			$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
			
			// balik urutan baris
			$rows = array_reverse($rows);

			return $rows;
		} else {
			return []; // Empty array
		}
	}



	// tampilkan semua class dengan status archive/complete
	public function get_complete_classes(){
		$result = $this->db->query("select * from tbl_classes where status_class = 'complete';");
		$this->db->db_close(); // Close database connection
		
		if ($result->num_rows > 0) {
			// konversi hasil query menjadi array asosiatif
			$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
			
			// balik urutan baris
			$rows = array_reverse($rows);

			return $rows;
		} else {
			return []; // Empty array
		}
	}



	// tampilkan profile
	
	public function get_info_operator($data)
	{
		$email = $data['email'];
		$password = $data['password'];
		$role = $_POST['select-role'];
		// case sensitive dengan menambahkan modifier BINARY sebelum kolom name
		$result = $this->db->query("select * from tbl_operator where role = '$role' AND email = '$email' AND BINARY password = '$password';");
		$this->db->db_close(); // Close database connection

		if ($result->num_rows > 0) {
			// convert to associative array
			$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
			return $rows;
		} else {
			return []; // kosong return false
		}
	}

	// check password status
	public function check_password($data){
		// variables
		$email = $data['email'];
		$password = $data['old_password'];

		// enkripsi password
		$pass_encripted = hash('md5', $password);
		
		// check password (case sensitive dengan menambahkan modifier BINARY sebelum kolom name)
		$result = $this->db->query("select password from tbl_operator where  email = '$email' AND BINARY password = '$pass_encripted';");
		$this->db->db_close(); // Close database connection

		if ($result->num_rows > 0) {
			// convert to associative array
			$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
			return $rows;
		} else {
			return []; // kosong return false
		}
	}

	// update password
	public function update_password($data){
		// Time zone
		//date_default_timezone_set('Asia/Jakarta');
		date_default_timezone_set('Asia/Makassar');
		
		// variables
		$email = $data['email'];
		$password = $data['new_password'];

		// enkripsi password
		$pass_encripted = hash('md5', $password);

		// Sql query to update status
		$sql = "UPDATE tbl_operator SET `password`='$pass_encripted', `updated_at`=NOW() WHERE email = '$email';";

		if ($this->db->query($sql) === TRUE) {
			return true;
		} else {
			return false;
		}
	}
	
	
	*/
	
}
?>