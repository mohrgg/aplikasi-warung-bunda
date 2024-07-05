document.addEventListener("DOMContentLoaded", (event) => {
  if (sessionStorage.getItem("expired_warning") === "true") {
    var expiredModal = new bootstrap.Modal(
      document.getElementById("expiredWarningModal")
    );
    expiredModal.show();
    sessionStorage.removeItem("expired_warning"); // Hapus peringatan setelah modal ditampilkan
  }
});

// Fungsi untuk menampilkan modal konfirmasi pembayaran dan mengatur nama pembeli
function setBuyerName() {
  var buyerName = document.getElementById("buyer_name").value;
  if (buyerName.trim() === "") {
    var buyerNameModal = new bootstrap.Modal(
      document.getElementById("buyerNameModal")
    );
    buyerNameModal.show();
    return false; // Batalkan submit form
  } else {
    document.getElementById("modalBuyerName").value = buyerName;
    return true; // Lanjutkan submit form
  }
}
