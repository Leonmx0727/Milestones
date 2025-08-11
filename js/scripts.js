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



function validateFetchLeagues(form) {
  const season = form.season.value.trim();
  const limit  = form.limit.value.trim();
  if (season && !/^\d{4}$/.test(season)) { alert("Season must be a 4-digit year."); return false; }
  if (!limit || +limit < 1 || +limit > 100) { alert("Limit must be between 1 and 100."); return false; }
  return true;
}

function validateFetchTeams(form) {
  const leagueId = form.league_id_api.value.trim();
  const season   = form.season.value.trim();
  const limit    = form.limit.value.trim();

  const name     = form.name.value.trim();
  const country  = form.country.value.trim();

  // If league is provided, require season
  if (leagueId && !/^\d{4}$/.test(season)) {
    alert("When providing a League ID, Season must be a 4-digit year.");
    return false;
  }
  // If NOT using league+season, allow name/country; but do NOT allow season alone
  if (!leagueId && season) {
    alert("Season can only be used with League ID. Remove Season or provide League ID.");
    return false;
  }

  if (!limit || +limit < 1 || +limit > 100) { alert("Limit must be between 1 and 100."); return false; }
  // At least one filter should be used
  if (!leagueId && !name && !country) {
    alert("Provide League+Season or use Name/Country to fetch teams.");
    return false;
  }
  return true;
}


function validateLeaguesListFilters(form) {
  const limit = form.limit.value.trim();
  const page  = form.page.value.trim();
  if (!limit || +limit < 1 || +limit > 100) { alert("Limit must be between 1 and 100."); return false; }
  if (page && +page < 1) { alert("Page must be 1 or greater."); return false; }
  return true;
}

function validateLeagueForm(form) {
  const name = form.name.value.trim();
  const type = form.type.value.trim();
  const logo = form.logo_url.value.trim();

  if (!name) { alert("League name is required."); return false; }
  if (type && !['League','Cup',''].includes(type)) {
    alert("Type must be 'League' or 'Cup'."); return false;
  }
  if (logo && !/^https?:\/\//i.test(logo)) {
    alert("Logo URL must start with http:// or https://"); return false;
  }
  return true;
}


function validateTeamsListFilters(form) {
  const limit = form.limit.value.trim();
  const page  = form.page.value.trim();
  if (!limit || +limit < 1 || +limit > 100) { alert("Limit must be between 1 and 100."); return false; }
  if (page && +page < 1) { alert("Page must be 1 or greater."); return false; }
  return true;
}

function validateTeamForm(form) {
  const name = form.name.value.trim();
  const code = form.code.value.trim();
  const logo = form.logo_url.value.trim();
  const founded = form.founded.value.trim();

  if (!name) { alert("Team name is required."); return false; }
  if (code && !/^[A-Za-z0-9]{2,10}$/.test(code)) { alert("Code should be 2–10 letters/numbers (no spaces)."); return false; }
  if (logo && !/^https?:\/\//i.test(logo)) { alert("Logo URL must start with http:// or https://"); return false; }
  if (founded && (!/^\d{4}$/.test(founded) || +founded < 1850 || +founded > 2100)) { alert("Founded must be a valid 4-digit year."); return false; }
  return true;
}
