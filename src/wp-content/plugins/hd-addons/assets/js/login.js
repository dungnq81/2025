document.addEventListener("DOMContentLoaded", function() {
  const login = document.getElementById("login");
  if (login) {
    const link = login.querySelector(".privacy-policy-page-link");
    if (link) link.remove();
  }
});
//# sourceMappingURL=login.js.map
