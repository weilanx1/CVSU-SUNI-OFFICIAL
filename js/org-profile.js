// Handles live UI selection preview changes
function previewImage(event) {
  const reader = new FileReader();
  reader.onload = function() {
    const output = document.getElementById('avatarImage');
    output.src = reader.result;
  }
  reader.readAsDataURL(event.target.files[0]);
}

// Hook ready for backend asynchronous operations (fetch/axios)
function handleSaveProfile() {
  const orgName = document.getElementById('orgName').value;
  
  // Ready to connect to API endpoint 
  console.log("Saving changes for:", orgName);
  alert("Profile configuration updated internally!");
}