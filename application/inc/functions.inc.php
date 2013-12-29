<?php

// Check credentials against db
function authenticateUser($username, $password) {
	try {
		$conn = new PDO('mysql:host=localhost;dbname=' . DB_DATABASE, DB_USER, DB_PASSWORD);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$stmt = $conn->prepare('SELECT * FROM adminusers WHERE username = :username AND password = :password');
	    $stmt->execute(array(
	    	'username' => $username,
	    	'password' => $password)
	    );

	    //MySQL will only return a value if a match found.
	    if($row = $stmt->fetch()) {
	    	return true;
	    }
	}
	catch(PDOException $e) {
	    echo 'ERROR: ' . $e->getMessage();
	}
}

function getTable() {
	try {
		$conn = new PDO('mysql:host=localhost;dbname=' . DB_DATABASE, DB_USER, DB_PASSWORD);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$stmt = $conn->prepare('SELECT * FROM property
			LEFT JOIN house_type ON house_type.house_type_id = property.house_type_id
			LEFT JOIN address ON address.address_id = property.address_id
			LEFT JOIN county ON county.county_id = address.county_id');
	    $stmt->execute();

	    //MySQL will only return a value if a match found.
	    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
	    return $results;
	    }
	catch(PDOException $e) {
	    echo 'ERROR: ' . $e->getMessage();
	}
}

function getCounties() {
	try {
		$conn = new PDO('mysql:host=localhost;dbname=' . DB_DATABASE, DB_USER, DB_PASSWORD);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$stmt = $conn->prepare('SELECT * FROM `county`');
	  $stmt->execute();

	    //MySQL will only return a value if a match found.
	    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
	    return $results;
	    }
	catch(PDOException $e) {
	    echo 'ERROR: ' . $e->getMessage();
	}
}

function getHouseTypes() {
	try {
		$conn = new PDO('mysql:host=localhost;dbname=' . DB_DATABASE, DB_USER, DB_PASSWORD);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$stmt = $conn->prepare('SELECT * FROM `house_type`');
	  $stmt->execute();

	    //MySQL will only return a value if a match found.
	    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
	    return $results;
	    }
	catch(PDOException $e) {
	    echo 'ERROR: ' . $e->getMessage();
	}
}




function getCountyId($countyName) {
	try {
		$conn = new PDO('mysql:host=localhost;dbname=' . DB_DATABASE, DB_USER, DB_PASSWORD);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$stmt = $conn->prepare('SELECT `county_id` FROM `county` WHERE `county_name` = :countyName');
		$stmt->bindParam(':countyName', $countyName, PDO::PARAM_STR);
	  $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$countyId = (int)$result[0]['county_id'];

		return $countyId;

	    }
	catch(PDOException $e) {
	    echo 'ERROR: ' . $e->getMessage();
	}
}

function getHouseTypeId($houseType) {
	try {
		$conn = new PDO('mysql:host=localhost;dbname=' . DB_DATABASE, DB_USER, DB_PASSWORD);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$stmt = $conn->prepare('SELECT `house_type_id` FROM `house_type` WHERE `house_type` = :houseType');
		$stmt->bindParam(':houseType', $houseType, PDO::PARAM_STR);
	  $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$houseTypeId = (int)$result[0]['house_type_id'];

		return $houseTypeId;

	    }
	catch(PDOException $e) {
	    echo 'ERROR: ' . $e->getMessage();
	}
}

function validateSubmissionTried() {

	// $tried is hidden input, value only created after user tries to submit.
	@$tried = ($_POST['tried'] == 'yes');
	$submissionTried = (!empty($tried));

	return $submissionTried;
}

// Supress errors as function returns false if any index not found anyway
function validatePost() {
	@$addressLine1 = $_POST['address_line_1'];
	@$town = $_POST['town'];
	@$monetaryValue = $_POST['monetary_value'];

	$validated = (!empty($addressLine1) && !empty($town));

	return $validated;
}

function savePropertyDetails() {

	// Variables
	$addressLine1 = $_POST['address_line_1'];
	$addressLine2 = isset($_POST['address_line_2']) ? $_POST['address_line_2'] : ""; 
	$town = $_POST['town'];
	$monetaryValue = $_POST['monetary_value'];
	$propertyImageName = uploadFiles() ? basename( $_FILES['property_image']['name']) : "default_house.jpg"; // If the file does not upload succesfully assign the default
	
	$countyName = $_POST['county_name'];
	$countyId = getCountyId($countyName);

	$houseType = $_POST['house_type'];
	$houseTypeId = getHouseTypeId($houseType);
	$propertyIsSold = true;
	
	
	$insertedAddressId = saveAddress($addressLine1, $addressLine2, $town, $countyId); // Post all needed data to the address table and get the address_id back as need to post that to property table
	saveProperty($insertedAddressId, $houseTypeId, $monetaryValue, $propertyIsSold, $propertyImageName); 	// Post data to the property table
	header("Location: view.php");
}

function saveAddress($addressLine1, $addressLine2, $town, $countyId) {

	try {
		$conn = new PDO('mysql:host=localhost;dbname=' . DB_DATABASE, DB_USER, DB_PASSWORD);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$stmt = $conn->prepare('INSERT INTO address (`address_line_1`, `address_line_2`, `town`, `county_id`)
			VALUES (:addressLine1, :addressLine2, :town, :countyId);');
		$stmt->bindParam(':addressLine1', $addressLine1, PDO::PARAM_STR);
		$stmt->bindParam(':addressLine2', $addressLine2, PDO::PARAM_STR);
		$stmt->bindParam(':town', $town, PDO::PARAM_STR);
		$stmt->bindParam(':countyId', $countyId, PDO::PARAM_INT);
		$stmt->execute();
		return $conn->lastInsertId('address_id');	
	}
	catch(PDOException $e) {
	    echo 'ERROR: ' . $e->getMessage();
	}
}

function saveProperty($addressId, $houseTypeId, $monetaryValue, $propertyIsSold, $propertyImageName) {

	try {
		$conn = new PDO('mysql:host=localhost;dbname=' . DB_DATABASE, DB_USER, DB_PASSWORD);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$stmt = $conn->prepare('INSERT INTO property (`address_id`, `house_type_id`, `monetary_value`, `property_is_sold`, `image_name`)
			VALUES (:addressId, :houseTypeId, :monetaryValue, :propertyIsSold, :imageName);');
		$stmt->bindParam(':addressId', $addressId, PDO::PARAM_INT);
		$stmt->bindParam(':houseTypeId', $houseTypeId, PDO::PARAM_INT);
		$stmt->bindParam(':monetaryValue', $monetaryValue, PDO::PARAM_INT);
		$stmt->bindParam(':propertyIsSold', $propertyIsSold, PDO::PARAM_INT);
		$stmt->bindParam(':imageName', $propertyImageName, PDO::PARAM_INT);
		$stmt->execute();	
	}
	catch(PDOException $e) {
	    echo 'ERROR: ' . $e->getMessage();
	}

}

function uploadFiles() {
	if (move_uploaded_file($_FILES['property_image']['tmp_name'], UPLOAD_PATH
		. '/' .$_FILES['property_image']['name'])) {
		$fileUploadedSuccessfully = true;
	}
	else {
		$fileUploadedSuccessfully = false;
	}	
	return $fileUploadedSuccessfully;
}

function deleteProperty($propertyId, $addressId) {
	try {
		$conn = new PDO('mysql:host=localhost;dbname=' . DB_DATABASE, DB_USER, DB_PASSWORD);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$stmt = $conn->prepare('DELETE FROM `property` WHERE `property_id` = :propertyId');
		$stmt->bindParam(':propertyId', $propertyId, PDO::PARAM_INT);
	  $stmt->execute();
	  $stmt = $conn->prepare('DELETE FROM `address` WHERE `address_id` = :addressId');
		$stmt->bindParam(':addressId', $addressId, PDO::PARAM_INT);
	  $stmt->execute();  
	    }
	catch(PDOException $e) {
	    echo 'ERROR: ' . $e->getMessage();
	}


}

?>