export async function handler() {
  try {
    const response = await fetch("https://goldbroker.com/widget/live-table/XAG/OMR", {
      headers: {
        "User-Agent": "Mozilla/5.0"
      }
    });

    if (!response.ok) {
      return {
        statusCode: 500,
        body: JSON.stringify({ error: "Failed to fetch data" })
      };
    }

    const html = await response.text();

    const match = html.match(/[\d]+[.,]\d+/);

    if (match) {
      return {
        statusCode: 200,
        body: JSON.stringify({ rate: match[0].replace(",", ".") })
      };
    }

    return {
      statusCode: 404,
      body: JSON.stringify({ error: "Rate not found" })
    };

  } catch (err) {
    return {
      statusCode: 500,
      body: JSON.stringify({ error: err.message })
    };
  }
}