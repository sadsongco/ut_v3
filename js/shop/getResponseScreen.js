const getResponseScreen = async (status) => {
  const url = `/functions/interface/shop/get_response_screen.php?status=${status}`;
  try {
    const res = await fetch(url);
    return await res.text();
  } catch (err) {
    return err.message;
  }
};
