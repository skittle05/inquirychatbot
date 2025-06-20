<?php
session_start();

include("connection.php");
include("functions.php");

$user_data = check_login($con);

// Initialize error message variable
$error_message = '';

// Handle chat messages
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['message'])) {
	$message = trim($_POST['message']);
	if (empty($message)) {
		header('Content-Type: application/json');
		echo json_encode(['error' => 'Message cannot be empty']);
		exit;
	}

	$user_id = $user_data['user_id'];

	try {
		// Ensure the chat_messages table exists
		$table_check = mysqli_query($con, "SHOW TABLES LIKE 'chat_messages'");
		if (mysqli_num_rows($table_check) == 0) {
			$create_table = "CREATE TABLE IF NOT EXISTS chat_messages (
				id INT AUTO_INCREMENT PRIMARY KEY,
				user_id VARCHAR(20) NOT NULL,
				role ENUM('user', 'assistant') NOT NULL,
				content TEXT NOT NULL,
				created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
				FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
			if (!mysqli_query($con, $create_table)) {
				throw new Exception("Failed to create chat_messages table: " . mysqli_error($con));
			}
		}

		// Save user message to database
		$query = "INSERT INTO chat_messages (user_id, role, content) VALUES (?, 'user', ?)";
		$stmt = mysqli_prepare($con, $query);
		if (!$stmt) {
			throw new Exception("Database error: " . mysqli_error($con));
		}
		mysqli_stmt_bind_param($stmt, "ss", $user_id, $message);
		if (!mysqli_stmt_execute($stmt)) {
			throw new Exception("Failed to save user message: " . mysqli_stmt_error($stmt));
		}

		// Generate a simple response
		$message_lower = strtolower($message);
		$words = explode(' ', $message_lower);

		// Define all keyword categories
		$greetings = ['hi', 'hello', 'hey', 'greetings'];
		$academic_keywords = ['course', 'class', 'study', 'grade', 'exam', 'test', 'assignment', 'homework'];
		$registration_keywords = ['register', 'enrollment', 'sign up', 'schedule'];
		$requirement_keywords = ['requirement', 'prerequisite', 'needed', 'mandatory', 'required'];
		$admission_keywords = ['admission', 'gcat', 'apply', 'application', 'test', 'requirements', 'documents', 'new student', 'transferee'];
		$financial_keywords = ['tuition', 'fee', 'payment', 'financial aid', 'scholarship', 'loan', 'grant'];
		$graduation_keywords = ['graduate', 'graduation', 'diploma', 'degree', 'commencement'];
		$document_keywords = ['transcript', 'form', 'document', 'certificate', 'id', 'card'];
		$facility_keywords = ['library', 'lab', 'gymnasium', 'cafeteria', 'dorm', 'facility'];
		$history_keywords = ['history', 'background', 'founded', 'established', 'origin', 'story', 'past', 'heritage'];
		$faculty_keywords = ['faculty', 'professor', 'instructor', 'dean', 'teacher', 'staff', 'coordinator', 'department'];
		$ccs_keywords = ['computer', 'it', 'cs', 'computing', 'multimedia', 'technology', 'ccs'];
		$contact_keywords = ['contact', 'phone', 'email', 'address', 'location', 'reach', 'find', 'telephone', 'call'];
		$enrollment_keywords = ['enroll', 'enrol', 'enrollment', 'enrolment', 'register', 'registration', 'enlisting', 'enlistment', 'clearance'];
		$mission_keywords = ['mission', 'vision', 'goal', 'purpose', 'objective', 'aim', 'value'];

		// Common misspellings and their corrections
		$corrections = [
			'registation' => 'registration',
			'addmission' => 'admission',
			'scholership' => 'scholarship',
			'finacial' => 'financial',
			'graducation' => 'graduation',
			'transscript' => 'transcript',
			'libary' => 'library',
			'dormatory' => 'dormitory',
			'requirment' => 'requirement',
			'prerequesite' => 'prerequisite',
			'tuision' => 'tuition',
			'enrolment' => 'enrollment',
			'hystory' => 'history',
			'histry' => 'history',
			'facultie' => 'faculty',
			'proffesor' => 'professor',
			'coordnator' => 'coordinator',
			'instrctor' => 'instructor',
			'adress' => 'address',
			'fone' => 'phone',
			'phon' => 'phone',
			'emil' => 'email',
			'locetion' => 'location',
			'contect' => 'contact',
			'admision' => 'admission',
			'aplicant' => 'applicant',
			'requrment' => 'requirement',
			'transfere' => 'transferee',
			'documets' => 'documents',
			'mision' => 'mission',
			'vission' => 'vision',
			'purpos' => 'purpose'
		];

		// Check for misspellings and suggest corrections
		$corrected_words = [];
		$has_corrections = false;
		foreach ($words as $word) {
			if (array_key_exists($word, $corrections)) {
				$corrected_words[] = $corrections[$word];
				$has_corrections = true;
			} else {
				$corrected_words[] = $word;
			}
		}

		// If there were corrections, suggest the correct spelling
		if ($has_corrections) {
			$corrected_message = implode(' ', $corrected_words);
			$ai_message = "I notice you might have meant: \"" . ucfirst($corrected_message) . "\"\n\n";
			$message_lower = $corrected_message; // Use corrected message for further processing
		}

		// Function to find similar keywords
		function findSimilarKeywords($word, $keywords)
		{
			$similar = [];
			foreach ($keywords as $keyword) {
				if (levenshtein($word, $keyword) <= 2) { // Allow 2 character differences
					$similar[] = $keyword;
				}
			}
			return $similar;
		}

		// Combine all keywords for similarity checking
		$all_keywords = array_merge(
			$academic_keywords,
			$registration_keywords,
			$requirement_keywords,
			$admission_keywords,
			$financial_keywords,
			$graduation_keywords,
			$document_keywords,
			$facility_keywords,
			$history_keywords,
			$faculty_keywords,
			$ccs_keywords,
			$contact_keywords,
			$mission_keywords
		);

		// Check for similar keywords if no direct matches
		$found_similar = false;
		$similar_suggestions = [];
		foreach ($words as $word) {
			$similar = findSimilarKeywords($word, $all_keywords);
			if (!empty($similar)) {
				$similar_suggestions = array_merge($similar_suggestions, $similar);
				$found_similar = true;
			}
		}

		// Process the message and generate response
		if (array_intersect($words, $greetings)) {
			$ai_message = "Hello! I'm your Gordon College AI assistant. How can I help you today with your academic queries?";
		} elseif (strpos($message_lower, 'thank') !== false) {
			$ai_message = "You're welcome! Feel free to ask if you have any other questions about Gordon College.";
		} elseif (array_intersect($words, $mission_keywords)) {
			$ai_message = "🎯 Gordon College Institutional Framework\n\n" .
				"🔹 MISSION:\n" .
				"To produce well-trained, skilled, dynamic and competitive individuals imbued with values " .
				"and attitudes responsive to the changing needs of the local, national and global communities.\n\n" .
				"🔹 VISION:\n" .
				"By 2025, the College envisions to be a premier local institution of higher learning in Region 3 " .
				"committed to the holistic development of the human person and society.\n\n" .
				"🔹 GOALS:\n" .
				"Gordon College shall:\n" .
				"1. Provide opportunities that will enable individuals to acquire a high level of professional, " .
				"technical and vocational courses of studies.\n\n" .
				"2. Develop innovative programs, projects, and models of practice by undertaking functional " .
				"and relevant research studies.\n\n" .
				"3. Promote community development through relevant extension programs.\n\n" .
				"4. Provide opportunities for employability and entrepreneurship of graduates.\n\n" .
				"🔹 CORE VALUES:\n" .
				"• EXCELLENCE\n" .
				"  Commitment to highest standards in education and service\n\n" .
				"• CHARACTER\n" .
				"  Development of strong moral and ethical principles\n\n" .
				"• SERVICE\n" .
				"  Dedication to serving the community and society\n\n" .
				"Implementation Strategies:\n" .
				"1. Academic Programs\n" .
				"   • Quality education delivery\n" .
				"   • Research-based learning\n" .
				"   • Industry-aligned curriculum\n\n" .
				"2. Student Development\n" .
				"   • Holistic formation\n" .
				"   • Leadership training\n" .
				"   • Career preparation\n\n" .
				"3. Community Engagement\n" .
				"   • Extension programs\n" .
				"   • Social responsibility\n" .
				"   • Partnership building\n\n" .
				"Would you like to know more about any specific aspect of our institutional framework?";
		} elseif (array_intersect($words, $admission_keywords)) {
			$ai_message = "🎓 Gordon College Admission Process (A.Y. 2025-2026)\n\n" .
				"Important Dates:\n" .
				"• GCAT Application Period: December 18, 2024 - March 14, 2025\n" .
				"• GCAT Start Date: March 3, 2025\n\n" .
				"Required Documents:\n" .
				"• Recent Good Moral Character Certificate\n" .
				"• PSA Authenticated Birth Certificate\n" .
				"• Senior High School ID or Valid ID Card\n\n" .
				"Application Procedure:\n\n" .
				"1. Create Online Portal Account\n" .
				"   • Visit: https://gordoncollege.edu.ph/gca/student/\n" .
				"   • Click 'Create Account'\n" .
				"   • Provide valid email and personal details\n" .
				"   • Select student type (New/Transferee/Second Course)\n\n" .
				"2. Account Login\n" .
				"   • Access portal with registered email\n" .
				"   • View application status on Dashboard\n\n" .
				"3. Complete Profile\n" .
				"   • Upload 2x2 photo (recent, white background)\n" .
				"   • Fill out personal information\n" .
				"   • Complete family background\n" .
				"   • Select desired programs\n" .
				"   • Accept privacy notice\n\n" .
				"4. Document Submission\n" .
				"   • Upload required documents\n" .
				"   • File size: less than 2MB each\n" .
				"   • Ensure clear, readable scans\n\n" .
				"5. GCAT Schedule\n" .
				"   • Check portal for test schedule\n" .
				"   • Download and print:\n" .
				"     - GCAT Schedule slip\n" .
				"     - Student Information Sheet\n\n" .
				"Test Day Requirements:\n" .
				"• GCAT Schedule Slip\n" .
				"• Student Information Sheet\n" .
				"• Valid ID\n" .
				"• Writing materials (pencil and pen)\n\n" .
				"Important Notes:\n" .
				"• Use only one account per applicant\n" .
				"• All applications must be submitted online\n" .
				"• Keep login credentials secure\n" .
				"• Photo requirements:\n" .
				"  - Formal pose in collared top\n" .
				"  - No eyeglasses or accessories\n" .
				"  - Taken within last month\n\n" .
				"Need help with any part of the admission process?";
		} elseif (array_intersect($words, $enrollment_keywords)) {
			$ai_message = "For registration-related queries, here's what you need to know:\n\n" .
				"1. Check your student portal for registration dates\n" .
				"2. Consult with your academic advisor\n" .
				"3. Review the course catalog\n" .
				"4. Ensure all prerequisites are met\n\n" .
				"Would you like more specific information about any of these steps?";
		} elseif (array_intersect($words, $contact_keywords)) {
			$ai_message = "Gordon College Contact Information:\n\n" .
				"📞 Telephone:\n" .
				"• (047) 222-4080\n\n" .
				"📧 Email:\n" .
				"• info@gordoncollege.edu.ph\n\n" .
				"📍 Location:\n" .
				"• Olongapo City Sports Complex\n" .
				"• Donor Street, East Tapinac\n" .
				"• Olongapo City 2200\n\n" .
				"Office Hours:\n" .
				"• Monday to Friday: 8:00 AM - 5:00 PM\n\n" .
				"How can we assist you today? Feel free to ask about:\n" .
				"• Admissions inquiries\n" .
				"• Program information\n" .
				"• General concerns\n" .
				"• Directions to campus\n\n" .
				"Would you like specific information about any department's contact details?";
		} elseif (array_intersect($words, $faculty_keywords) && array_intersect($words, $ccs_keywords)) {
			$ai_message = "College of Computer Studies Organizational Chart (2022-2023):\n\n" .
				"Administration:\n" .
				"• Dean: Dr. Erlinda Cabral-Arriola, DIT\n" .
				"• Assistant Dean: Ronnie D. Luy, MIT\n\n" .
				"Program Coordinators:\n" .
				"• BS Information Technology: Ronnie D. Luy, MIT\n" .
				"• BS Computer Science: Kenneth V. Bautista, MSCS\n" .
				"• BS Entertainment and Multimedia Computing: Paul Vincent P. Corsina\n" .
				"• Associate in Computer Technology: Denise Lou B. Punzalan, MSCS\n\n" .
				"Full-Time Faculty Members:\n\n" .
				"BS Information Technology Department:\n" .
				"• Annilyn T. Martinez, MST\n" .
				"• Mayer Z. Sanchez, MBA\n" .
				"• Denise Lou B. Punzalan, MSCS\n" .
				"• Gebald Jun R. Inocencio\n\n" .
				"BS Computer Science Department:\n" .
				"• Reynaldo G. Bautista Jr., MSCS\n" .
				"• Loudel L. Manaloto\n" .
				"• Denise Lou B. Punzalan, MSCS\n" .
				"• Haidee L. Hibocos\n\n" .
				"BS Entertainment and Multimedia Computing Department:\n" .
				"• Sean Patrick A. Wicker\n\n" .
				"Would you like specific information about any of our programs or faculty members?";
		} elseif (array_intersect($words, $history_keywords)) {
			$ai_message = "History of Gordon College:\n\n" .
				"Origins:\n" .
				"• Started as Olongapo City Training Center\n" .
				"• Initially trained skilled workers for US Naval facility\n" .
				"• Transformed into Olongapo City Colleges in 1999\n\n" .
				"Early Development:\n" .
				"• Initially offered BS in Accountancy and Computer Studies\n" .
				"• Started with 177 enrollees\n" .
				"• Expanded programs based on community needs\n\n" .
				"Key Milestones:\n" .
				"• 2002: Renamed to Gordon College (City Ordinance no. 42)\n" .
				"• 2002: Joined Association of Local Colleges and Universities (ALCU)\n" .
				"• 2004: Granted operational autonomy by CHED Region III\n" .
				"• 2004: Launched graduate programs in Education, Public Management, and Business Administration\n" .
				"• 2018: Charter revised through City Ordinance No. 07\n\n" .
				"Legacy:\n" .
				"• Named in honor of the Gordon family\n" .
				"• Established during Mayor Katherine H. Gordon's term\n" .
				"• Continues to serve Olongapo City residents\n\n" .
				"Would you like to know more about any specific period in the college's history?";
		} elseif (array_intersect($words, $requirement_keywords)) {
			$ai_message = "Here are the key requirements at Gordon College:\n\n" .
				"Academic Requirements:\n" .
				"• Maintain minimum GPA of 2.0\n" .
				"• Complete required core curriculum\n" .
				"• Fulfill major-specific requirements\n" .
				"• Meet attendance requirements\n\n" .
				"Administrative Requirements:\n" .
				"• Valid student ID\n" .
				"• Updated contact information\n" .
				"• Completed health records\n" .
				"• Signed honor code agreement\n\n" .
				"Would you like specific details about any of these requirements?";
		} elseif (array_intersect($words, $financial_keywords)) {
			$ai_message = "Financial Information:\n\n" .
				"1. Tuition and Fees:\n" .
				"   • Payment deadlines\n" .
				"   • Payment plans available\n" .
				"   • Online payment portal\n\n" .
				"2. Financial Aid:\n" .
				"   • Scholarship opportunities\n" .
				"   • Grant applications\n" .
				"   • Student loans\n" .
				"   • Work-study programs\n\n" .
				"Would you like specific details about costs or financial aid options?";
		} elseif (array_intersect($words, $graduation_keywords)) {
			$ai_message = "Graduation Requirements:\n\n" .
				"1. Academic Requirements:\n" .
				"   • Complete required credits\n" .
				"   • Maintain minimum GPA\n" .
				"   • Complete major requirements\n" .
				"   • Pass comprehensive exams\n\n" .
				"2. Administrative Requirements:\n" .
				"   • Apply for graduation\n" .
				"   • Clear all financial obligations\n" .
				"   • Return borrowed materials\n" .
				"   • Complete exit interviews\n\n" .
				"Need more specific information about graduation requirements?";
		} elseif (array_intersect($words, $document_keywords)) {
			$ai_message = "Document Request Procedures:\n\n" .
				"1. Official Transcripts:\n" .
				"   • Online request system\n" .
				"   • Processing time: 3-5 days\n" .
				"   • Available in digital/physical format\n\n" .
				"2. Other Documents:\n" .
				"   • Enrollment verification\n" .
				"   • Student ID replacement\n" .
				"   • Diploma duplicate\n" .
				"   • Certificate requests\n\n" .
				"Which document would you like to know more about?";
		} elseif (array_intersect($words, $facility_keywords)) {
			$ai_message = "Campus Facilities Information:\n\n" .
				"1. Library:\n" .
				"   • Operating hours: 7AM-10PM\n" .
				"   • Study rooms available\n" .
				"   • Digital resources access\n\n" .
				"2. Laboratories:\n" .
				"   • Computer labs\n" .
				"   • Science labs\n" .
				"   • Language labs\n\n" .
				"3. Other Facilities:\n" .
				"   • Cafeteria hours\n" .
				"   • Gymnasium access\n" .
				"   • Dormitory rules\n\n" .
				"Which facility would you like to know more about?";
		} elseif (array_intersect($words, $academic_keywords)) {
			$ai_message = "I understand you have a question about academics. At Gordon College, we offer comprehensive academic support including:\n\n" .
				"• One-on-one tutoring sessions\n" .
				"• Study groups and workshops\n" .
				"• Academic advisors for guidance\n" .
				"• Library resources and research help\n\n" .
				"Could you please specify what particular academic assistance you need?";
		} elseif (strpos($message_lower, 'latest') !== false || strpos($message_lower, 'announcement') !== false || strpos($message_lower, 'news') !== false) {
			$ai_message = "📢 Latest Announcements from Gordon College:\n\n" .
				"1. Enrollment for Academic Year 2024-2025\n" .
				"   • Online enrollment is now ongoing\n" .
				"   • Early bird registration until March 31, 2024\n" .
				"   • Special discount for early enrollees\n\n" .
				"2. GCAT Schedule Update\n" .
				"   • Next GCAT batch: March 3, 2025\n" .
				"   • Online application portal now open\n" .
				"   • Results will be released within 5 working days\n\n" .
				"3. Academic Calendar Updates\n" .
				"   • First Semester: August 2024 - December 2024\n" .
				"   • Second Semester: January 2025 - May 2025\n" .
				"   • Summer Term: June 2025 - July 2025\n\n" .
				"4. Scholarship Applications\n" .
				"   • Now accepting applications for Academic Excellence Scholarship\n" .
				"   • Sports and Cultural Arts Scholarship available\n" .
				"   • Deadline: April 30, 2024\n\n" .
				"5. Campus Facilities Update\n" .
				"   • New Computer Laboratory opening in June 2024\n" .
				"   • Library renovation completed\n" .
				"   • Extended study areas now available\n\n" .
				"6. Academic Programs\n" .
				"   • New courses being offered for AY 2024-2025\n" .
				"   • Enhanced curriculum for IT and Computer Science\n" .
				"   • Industry partnership programs expanded\n\n" .
				"For more detailed information, please visit:\n" .
				"• Official website: www.gordoncollege.edu.ph\n" .
				"• Facebook page: Gordon College Official\n" .
				"• Student Portal\n\n" .
				"Would you like specific details about any of these announcements?";
		} else {
			// If no direct matches but found similar keywords
			if ($found_similar) {
				$ai_message = "I'm not quite sure what you're asking about. Did you mean to ask about any of these topics?\n\n";
				$suggested_topics = array_unique($similar_suggestions);
				foreach ($suggested_topics as $topic) {
					$ai_message .= "• " . ucfirst($topic) . "\n";
				}
				$ai_message .= "\nPlease try rephrasing your question using one of these terms, or let me know if you need help with something else.";
			} else {
				// No matches at all - provide general guidance
				$ai_message = "I'm not sure I understood your question about '" . htmlspecialchars($message) . "'. Here are some topics I can help you with:\n\n" .
					"1. Academic Information:\n" .
					"   • Courses and Classes\n" .
					"   • Grades and Exams\n" .
					"   • Study Resources\n\n" .
					"2. Administrative Procedures:\n" .
					"   • Registration\n" .
					"   • Admission\n" .
					"   • Document Requests\n\n" .
					"3. Campus Services:\n" .
					"   • Financial Aid\n" .
					"   • Facilities\n" .
					"   • Student Support\n\n" .
					"Please try asking about one of these topics, or rephrase your question to be more specific.";
			}
		}

		// Add a helpful tip for better results
		if (!array_intersect($words, $greetings) && !strpos($message_lower, 'thank') !== false) {
			$ai_message .= "\n\nTip: For better results, try to include specific keywords like 'registration', 'admission', 'courses', etc.";
		}

		// Save AI response to database
		$query = "INSERT INTO chat_messages (user_id, role, content) VALUES (?, 'assistant', ?)";
		$stmt = mysqli_prepare($con, $query);
		if (!$stmt) {
			throw new Exception("Database error: " . mysqli_error($con));
		}
		mysqli_stmt_bind_param($stmt, "ss", $user_id, $ai_message);
		if (!mysqli_stmt_execute($stmt)) {
			throw new Exception("Failed to save AI response: " . mysqli_stmt_error($stmt));
		}

		// Return success response
		header('Content-Type: application/json');
		echo json_encode(['response' => $ai_message]);
	} catch (Exception $e) {
		error_log("Chat error: " . $e->getMessage());
		header('Content-Type: application/json');
		echo json_encode(['error' => $e->getMessage()]);
	}
	exit;
}

// Get chat history
$chat_history = [];
try {
	if (isset($user_data['user_id'])) {
		$query = "SELECT * FROM chat_messages WHERE user_id = ? ORDER BY created_at ASC";
		$stmt = mysqli_prepare($con, $query);
		if (!$stmt) {
			throw new Exception("Failed to prepare chat history query: " . mysqli_error($con));
		}
		mysqli_stmt_bind_param($stmt, "s", $user_data['user_id']);
		if (!mysqli_stmt_execute($stmt)) {
			throw new Exception("Failed to execute chat history query: " . mysqli_stmt_error($stmt));
		}
		$result = mysqli_stmt_get_result($stmt);
		while ($row = mysqli_fetch_assoc($result)) {
			$chat_history[] = $row;
		}
	}
} catch (Exception $e) {
	error_log("Error fetching chat history: " . $e->getMessage());
	$error_message = "Failed to load chat history";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>AI Chat Assistant</title>
	<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
	<style>
		* {
			margin: 0;
			padding: 0;
			box-sizing: border-box;
			font-family: 'Roboto', sans-serif;
		}

		body {
			background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
			min-height: 100vh;
			display: flex;
			flex-direction: column;
			align-items: center;
		}

		.user-header {
			width: 100%;
			background: rgba(255, 255, 255, 0.9);
			padding: 1rem;
			text-align: center;
			box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
			margin-bottom: 2rem;
		}

		.user-header h1 {
			color: #333;
			font-size: 1.5rem;
			margin-bottom: 0.5rem;
		}

		.logout-btn {
			background: #4a90e2;
			color: white;
			padding: 0.5rem 1rem;
			border-radius: 20px;
			text-decoration: none;
			transition: background 0.3s ease;
			font-size: 0.9rem;
		}

		.logout-btn:hover {
			background: #357abd;
		}

		.chatbot {
			width: 90%;
			max-width: 800px;
			height: 80vh;
			background: white;
			border-radius: 20px;
			box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
			overflow: hidden;
			display: flex;
			flex-direction: column;
		}

		.chat-header {
			background: #4a90e2;
			color: white;
			padding: 1.5rem;
			display: flex;
			justify-content: space-between;
			align-items: center;
		}

		.chat-header h2 {
			font-size: 1.5rem;
			font-weight: 500;
		}

		.chatbox {
			flex: 1;
			padding: 2rem;
			overflow-y: auto;
			background: #f8f9fa;
		}

		.chat {
			margin: 1rem 0;
			max-width: 80%;
			padding: 1rem;
			border-radius: 15px;
			position: relative;
			animation: fadeIn 0.3s ease;
		}

		@keyframes fadeIn {
			from {
				opacity: 0;
				transform: translateY(10px);
			}

			to {
				opacity: 1;
				transform: translateY(0);
			}
		}

		.incoming {
			background: white;
			margin-right: auto;
			border-bottom-left-radius: 5px;
			box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
		}

		.outgoing {
			background: #4a90e2;
			color: white;
			margin-left: auto;
			border-bottom-right-radius: 5px;
			box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
		}

		.chat-input {
			padding: 1.5rem;
			background: white;
			border-top: 1px solid #eee;
			display: flex;
			gap: 1rem;
			align-items: center;
		}

		.chat-input textarea {
			flex: 1;
			padding: 1rem;
			border: 1px solid #e0e0e0;
			border-radius: 25px;
			resize: none;
			font-size: 1rem;
			line-height: 1.5;
			max-height: 100px;
			transition: border-color 0.3s ease;
		}

		.chat-input textarea::placeholder {
			text-align: center;
			line-height: 40px;
			/* Adjust this value to vertically center the placeholder */
			color: #888;
			font-size: 0.95rem;
		}

		.chat-input textarea:focus {
			outline: none;
			border-color: #4a90e2;
			text-align: left;
			/* Reset alignment when user starts typing */
		}

		#send-btn {
			background: #4a90e2;
			color: white;
			width: 50px;
			height: 50px;
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			cursor: pointer;
			transition: transform 0.2s ease;
		}

		#send-btn:hover {
			transform: scale(1.1);
		}

		.material-icons {
			font-size: 24px;
		}

		/* Chat Head Styles */
		.chat-head {
			position: fixed;
			bottom: 20px;
			right: 20px;
			width: 60px;
			height: 60px;
			background: #4a90e2;
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			cursor: pointer;
			box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
			transition: transform 0.3s ease;
			z-index: 1000;
		}

		.chat-head:hover {
			transform: scale(1.1);
		}

		.chat-head .material-icons {
			color: white;
			font-size: 28px;
		}

		/* FAQ Container Styles */
		.faq-container {
			width: 90%;
			max-width: 800px;
			margin: 2rem auto;
			background: white;
			border-radius: 20px;
			box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
			padding: 2rem;
		}

		.faq-header {
			text-align: center;
			margin-bottom: 2rem;
			color: #333;
		}

		.faq-item {
			margin-bottom: 1rem;
			border-bottom: 1px solid #eee;
			padding-bottom: 1rem;
		}

		.faq-question {
			font-weight: 500;
			color: #4a90e2;
			cursor: pointer;
			display: flex;
			justify-content: space-between;
			align-items: center;
			padding: 1rem;
			background: #f8f9fa;
			border-radius: 10px;
			transition: background 0.3s ease;
		}

		.faq-question:hover {
			background: #e9ecef;
		}

		.faq-answer {
			padding: 1rem;
			color: #666;
			display: none;
		}

		.faq-answer.active {
			display: block;
		}

		.faq-question .material-icons {
			transition: transform 0.3s ease;
		}

		.faq-question.active .material-icons {
			transform: rotate(180deg);
		}

		@media (max-width: 768px) {
			.chatbot {
				width: 100%;
				height: 90vh;
				border-radius: 0;
			}

			.chat-header h2 {
				font-size: 1.2rem;
			}

			.chat {
				max-width: 90%;
			}

			.faq-container {
				width: 95%;
				padding: 1rem;
			}
		}

		.typing-indicator {
			display: flex;
			gap: 0.5rem;
			padding: 0.5rem;
			background: rgba(0, 0, 0, 0.05);
			border-radius: 20px;
			width: fit-content;
		}

		.typing-dot {
			width: 8px;
			height: 8px;
			background: #4a90e2;
			border-radius: 50%;
			animation: typing 1s infinite ease-in-out;
		}

		.typing-dot:nth-child(2) {
			animation-delay: 0.2s;
		}

		.typing-dot:nth-child(3) {
			animation-delay: 0.4s;
		}

		@keyframes typing {

			0%,
			100% {
				transform: translateY(0);
			}

			50% {
				transform: translateY(-5px);
			}
		}

		/* Add this to the existing style section */
		.chat.incoming .flex {
			display: flex;
			align-items: center;
			gap: 8px;
		}

		.chat.incoming .material-icons {
			font-size: 20px;
			animation: float 3s ease-in-out infinite;
		}

		@keyframes float {

			0%,
			100% {
				transform: translateY(0);
			}

			50% {
				transform: translateY(-3px);
			}
		}

		.chat.incoming:first-child {
			margin-top: 0;
			background: #f0f7ff;
			border-left: 3px solid #4a90e2;
		}
	</style>
</head>

<body>
	<div class="user-header">
		<h1>Welcome, <?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?>!</h1>
		<div class="flex justify-center gap-4">
			<a href="change_password.php" class="text-primary hover:text-secondary">Change Password</a>
			<span class="text-gray-300">|</span>
			<a href="logout.php" class="logout-btn">Logout</a>
		</div>
	</div>

	<!-- FAQ Container -->
	<div class="faq-container">
		<div class="faq-header">
			<h2>Gordon College FAQs</h2>
			<p>Frequently Asked Questions about Gordon College</p>
		</div>
		<div class="faq-list">
			<!-- Latest Announcements -->
			<div class="faq-item">
				<div class="faq-question">
					<span>What are the latest announcements from Gordon College?</span>
					<span class="material-icons">expand_more</span>
				</div>
				<div class="faq-answer">
					Current important announcements include:
					<ul>
						<li>Enrollment for Academic Year 2024-2025 is ongoing</li>
						<li>GCAT Schedule for March 3, 2025</li>
						<li>New Academic Calendar Updates</li>
						<li>Scholarship Applications now open</li>
						<li>New Computer Laboratory opening in June 2024</li>
					</ul>
				</div>
			</div>

			<!-- Admission Process -->
			<div class="faq-item">
				<div class="faq-question">
					<span>What is the admission process for new students?</span>
					<span class="material-icons">expand_more</span>
				</div>
				<div class="faq-answer">
					The admission process includes:
					<ul>
						<li>GCAT Application (December 18, 2024 - March 14, 2025)</li>
						<li>Required Documents:
							<ul>
								<li>Good Moral Character Certificate</li>
								<li>PSA Authenticated Birth Certificate</li>
								<li>Senior High School ID or Valid ID Card</li>
							</ul>
						</li>
						<li>Online Portal Registration</li>
						<li>Document Submission</li>
						<li>GCAT Examination</li>
					</ul>
				</div>
			</div>

			<!-- Mission and Vision -->
			<div class="faq-item">
				<div class="faq-question">
					<span>What is Gordon College's Mission and Vision?</span>
					<span class="material-icons">expand_more</span>
				</div>
				<div class="faq-answer">
					<strong>Mission:</strong> To produce well-trained, skilled, dynamic and competitive individuals imbued with values and attitudes responsive to the changing needs of the local, national and global communities.<br><br>
					<strong>Vision:</strong> By 2025, the College envisions to be a premier local institution of higher learning in Region 3 committed to the holistic development of the human person and society.
				</div>
			</div>

			<!-- Contact Information -->
			<div class="faq-item">
				<div class="faq-question">
					<span>How can I contact Gordon College?</span>
					<span class="material-icons">expand_more</span>
				</div>
				<div class="faq-answer">
					You can reach Gordon College through:
					<ul>
						<li>📞 Telephone: (047) 222-4080</li>
						<li>📧 Email: info@gordoncollege.edu.ph</li>
						<li>📍 Address: Olongapo City Sports Complex, Donor Street, East Tapinac, Olongapo City 2200</li>
						<li>Office Hours: Monday to Friday, 8:00 AM - 5:00 PM</li>
					</ul>
				</div>
			</div>

			<!-- Academic Programs -->
			<div class="faq-item">
				<div class="faq-question">
					<span>What academic programs are available?</span>
					<span class="material-icons">expand_more</span>
				</div>
				<div class="faq-answer">
					The College of Computer Studies offers:
					<ul>
						<li>BS Information Technology</li>
						<li>BS Computer Science</li>
						<li>BS Entertainment and Multimedia Computing</li>
						<li>Associate in Computer Technology</li>
					</ul>
					Contact the admissions office for information about other colleges and programs.
				</div>
			</div>

			<!-- Financial Information -->
			<div class="faq-item">
				<div class="faq-question">
					<span>What financial assistance options are available?</span>
					<span class="material-icons">expand_more</span>
				</div>
				<div class="faq-answer">
					Financial assistance options include:
					<ul>
						<li>Academic Excellence Scholarship</li>
						<li>Sports and Cultural Arts Scholarship</li>
						<li>Student Loans</li>
						<li>Work-Study Programs</li>
						<li>Grant Applications</li>
					</ul>
					Deadline for scholarship applications: April 30, 2024
				</div>
			</div>

			<!-- Facilities -->
			<div class="faq-item">
				<div class="faq-question">
					<span>What facilities are available on campus?</span>
					<span class="material-icons">expand_more</span>
				</div>
				<div class="faq-answer">
					Campus facilities include:
					<ul>
						<li>Library (Operating hours: 7AM-10PM)</li>
						<li>Computer Laboratories</li>
						<li>Science Laboratories</li>
						<li>Language Laboratories</li>
						<li>Gymnasium</li>
						<li>Cafeteria</li>
						<li>Study Areas</li>
					</ul>
				</div>
			</div>

			<!-- Document Requests -->
			<div class="faq-item">
				<div class="faq-question">
					<span>How do I request official documents?</span>
					<span class="material-icons">expand_more</span>
				</div>
				<div class="faq-answer">
					Document request procedures:
					<ul>
						<li>Official Transcripts (3-5 days processing)</li>
						<li>Enrollment Verification</li>
						<li>Student ID Replacement</li>
						<li>Diploma Duplicate</li>
						<li>Certificate Requests</li>
					</ul>
					Visit the Registrar's Office or use the online request system.
				</div>
			</div>

			<!-- Graduation Requirements -->
			<div class="faq-item">
				<div class="faq-question">
					<span>What are the graduation requirements?</span>
					<span class="material-icons">expand_more</span>
				</div>
				<div class="faq-answer">
					To graduate, students must:
					<ul>
						<li>Complete required credits</li>
						<li>Maintain minimum GPA of 2.0</li>
						<li>Complete major requirements</li>
						<li>Pass comprehensive exams</li>
						<li>Clear all financial obligations</li>
						<li>Complete exit interviews</li>
					</ul>
				</div>
			</div>

			<!-- Academic Calendar -->
			<div class="faq-item">
				<div class="faq-question">
					<span>What is the academic calendar for 2024-2025?</span>
					<span class="material-icons">expand_more</span>
				</div>
				<div class="faq-answer">
					Academic Year 2024-2025:
					<ul>
						<li>First Semester: August 2024 - December 2024</li>
						<li>Second Semester: January 2025 - May 2025</li>
						<li>Summer Term: June 2025 - July 2025</li>
						<li>Early bird registration until March 31, 2024</li>
					</ul>
				</div>
			</div>
		</div>
	</div>

	<!-- Chat Head Button -->
	<div class="chat-head" onclick="toggleChat()">
		<span class="material-icons">chat</span>
	</div>

	<!-- Existing Chatbot -->
	<div class="chatbot" style="display: none;">
		<div class="chat-header">
			<h2>AI Chat Assistant</h2>
			<span class="material-icons">smart_toy</span>
		</div>
		<div class="chatbox" id="chatbox">
			<!-- Welcome Message -->
			<div class="chat incoming">
				<div class="flex items-center gap-2">
					<span class="material-icons" style="color: #4a90e2;">smart_toy</span>
					<span>Hello! How can I assist you today?</span>
				</div>
			</div>

			<?php foreach ($chat_history as $message): ?>
				<div class="chat <?php echo $message['role'] === 'user' ? 'outgoing' : 'incoming'; ?>">
					<?php echo nl2br(htmlspecialchars($message['content'])); ?>
				</div>
			<?php endforeach; ?>
		</div>
		<div class="chat-input">
			<textarea id="messageInput" placeholder="Type your message here..." rows="1"></textarea>
			<div id="send-btn">
				<span class="material-icons">send</span>
			</div>
		</div>
	</div>

	<script>
		const messagesContainer = document.getElementById('chatbox');
		const messageInput = document.getElementById('messageInput');
		const sendButton = document.getElementById('send-btn');

		// Scroll to bottom of messages
		function scrollToBottom() {
			messagesContainer.scrollTop = messagesContainer.scrollHeight;
		}

		// Add message to chat
		function addMessage(content, isUser = true) {
			const messageDiv = document.createElement('div');
			messageDiv.className = `chat ${isUser ? 'outgoing' : 'incoming'}`;
			messageDiv.innerHTML = content.replace(/\n/g, '<br>');
			messagesContainer.appendChild(messageDiv);
			scrollToBottom();
		}

		// Show typing indicator
		function showTypingIndicator() {
			const indicator = document.createElement('div');
			indicator.className = 'typing-indicator';
			indicator.innerHTML = `
				<div class="typing-dot"></div>
				<div class="typing-dot"></div>
				<div class="typing-dot"></div>
			`;
			messagesContainer.appendChild(indicator);
			scrollToBottom();
			return indicator;
		}

		// Send message
		function sendMessage() {
			const message = messageInput.value.trim();
			if (!message) return;

			// Add user message
			addMessage(message, true);
			messageInput.value = '';

			// Show typing indicator
			const typingIndicator = showTypingIndicator();

			// Send to server
			fetch(window.location.href, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					body: `message=${encodeURIComponent(message)}`
				})
				.then(response => response.json())
				.then(data => {
					// Remove typing indicator
					typingIndicator.remove();
					// Add AI response
					addMessage(data.response, false);
				})
				.catch(error => {
					console.error('Error:', error);
					typingIndicator.remove();
					addMessage('Sorry, there was an error processing your request.', false);
				});
		}

		// Event listeners
		messageInput.addEventListener('keypress', function(e) {
			if (e.key === 'Enter' && !e.shiftKey) {
				e.preventDefault();
				sendMessage();
			}
		});

		sendButton.addEventListener('click', sendMessage);

		// Auto-resize textarea
		messageInput.addEventListener('input', function() {
			this.style.height = 'auto';
			this.style.height = (this.scrollHeight) + 'px';
		});

		// Initial scroll to bottom
		scrollToBottom();

		// Toggle chat visibility
		function toggleChat() {
			const chatbot = document.querySelector('.chatbot');
			if (chatbot.style.display === 'none') {
				chatbot.style.display = 'flex';
				scrollToBottom();
			} else {
				chatbot.style.display = 'none';
			}
		}

		// FAQ Toggle functionality
		document.querySelectorAll('.faq-question').forEach(question => {
			question.addEventListener('click', () => {
				const answer = question.nextElementSibling;
				const isOpen = answer.classList.contains('active');

				// Close all answers
				document.querySelectorAll('.faq-answer').forEach(a => a.classList.remove('active'));
				document.querySelectorAll('.faq-question').forEach(q => q.classList.remove('active'));

				// Open clicked answer if it was closed
				if (!isOpen) {
					answer.classList.add('active');
					question.classList.add('active');
				}
			});
		});
	</script>
</body>

</html>