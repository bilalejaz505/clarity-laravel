const memberSidebar = document.getElementById("member-sidebar");
const memberNavOpen = document.getElementById("member-nav-open");
const memberNavClose = document.getElementById("member-nav-close");

memberNavOpen?.addEventListener("click", () =>
    memberSidebar?.classList.add("mobile-open"),
);
memberNavClose?.addEventListener("click", () =>
    memberSidebar?.classList.remove("mobile-open"),
);
