const adminRoot = document.querySelector("#admin-charts");
if (adminRoot && window.Chart) {
  let storiesSeries = [];
  let usersSeries = [];

  try {
    storiesSeries = JSON.parse(adminRoot.dataset.storiesSeries || "[]");
  } catch (e) {
    console.warn("Failed to parse storiesSeries", e);
  }

  try {
    usersSeries = JSON.parse(adminRoot.dataset.usersSeries || "[]");
  } catch (e) {
    console.warn("Failed to parse usersSeries", e);
  }

  const makeDataset = (series) => ({
    labels: series.map((p) => p.bucket),
    data: series.map((p) => p.cnt),
  });

  const s = makeDataset(storiesSeries);
  const u = makeDataset(usersSeries);

  const storiesCanvas = document.querySelector("#storiesChart");
  if (storiesCanvas) {
    new Chart(storiesCanvas, {
      type: "line",
      data: {
        labels: s.labels,
        datasets: [
          {
            label: "Stories",
            data: s.data,
            fill: false,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
      },
    });
  }

  const usersCanvas = document.querySelector("#usersChart");
  if (usersCanvas) {
    new Chart(usersCanvas, {
      type: "line",
      data: {
        labels: u.labels,
        datasets: [
          {
            label: "Users",
            data: u.data,
            fill: false,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
      },
    });
  }
}
