// UCID: lm64 | Date: 09/08/2025
// Details: Basic client-side validation for forms.

function validateRegistration(form) {
  // Username: 3-20 chars letters/numbers/underscore
  const username = form.username.value.trim();
  if (!/^[A-Za-z0-9_]{3,20}$/.test(username)) {
    alert("Username should be 3–20 characters (letters, numbers, underscore).");
    return false;
  }
  const email = form.email.value.trim();
  if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) {
    alert("Please enter a valid email address.");
    return false;
  }
  const pw = form.password.value;
  const cpw = form.confirm_password.value;
  if (pw.length < 8) {
    alert("Password must be at least 8 characters.");
    return false;
  }
  if (pw !== cpw) {
    alert("Passwords do not match.");
    return false;
  }
  return true;
}

function validateLogin(form) {
  const u = form.username_or_email.value.trim();
  const pw = form.password.value;
  if (!u) { alert("Please enter your username or email."); return false; }
  if (!pw) { alert("Please enter your password."); return false; }
  return true;
}

function validateProfileAccount(form) {
  const username = form.username.value.trim();
  const email = form.email.value.trim();
  if (!/^[A-Za-z0-9_]{3,20}$/.test(username)) {
    alert("Username should be 3–20 characters (letters, numbers, underscore).");
    return false;
  }
  if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) {
    alert("Please enter a valid email address.");
    return false;
  }
  return true;
}

function validateProfilePassword(form) {
  const cur = form.current_password.value;
  const npw = form.new_password.value;
  const cpw = form.confirm_new_password.value;
  if (!cur) { alert("Please enter your current password."); return false; }
  if (npw.length < 8) { alert("New password must be at least 8 characters."); return false; }
  if (npw !== cpw) { alert("New passwords do not match."); return false; }
  return true;
}
