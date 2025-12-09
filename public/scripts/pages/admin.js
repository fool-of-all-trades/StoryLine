const adminRoot = document.querySelector("#admin-charts");

function initAdminCharts() {
  if (!adminRoot || !window.Chart) return;

  // Parse chart data from dataset attributes
  const parseChartData = (datasetKey) => {
    try {
      return JSON.parse(adminRoot.dataset[datasetKey] || "[]");
    } catch (e) {
      console.warn(`Failed to parse ${datasetKey}:`, e);
      return [];
    }
  };

  const storiesSeries = parseChartData("storiesSeries");
  const usersSeries = parseChartData("usersSeries");

  // Transform series data into Chart.js format
  const transformSeries = (series) => ({
    labels: series.map((point) => point.bucket),
    data: series.map((point) => point.cnt),
  });

  // Create a line chart with given configuration
  const createLineChart = (canvasSelector, label, series) => {
    const canvas = document.querySelector(canvasSelector);
    if (!canvas) return;

    const chartData = transformSeries(series);

    new Chart(canvas, {
      type: "line",
      data: {
        labels: chartData.labels,
        datasets: [
          {
            label,
            data: chartData.data,
            fill: false,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
      },
    });
  };

  // Initialize charts
  createLineChart("#storiesChart", "Stories", storiesSeries);
  createLineChart("#usersChart", "Users", usersSeries);
}

initAdminCharts();
