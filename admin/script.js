// Sample fundi data (simulates submissions)
let fundis = [
    {
        name: "John Mwangi",
        skill: "Plumber",
        idImage: "https://via.placeholder.com/100",
        faceImage: "https://via.placeholder.com/100",
        ocrStatus: "Verified",
        faceMatch: "Matched",
        status: "Pending"
    },
    {
        name: "Jane Akinyi",
        skill: "Electrician",
        idImage: "https://via.placeholder.com/100",
        faceImage: "https://via.placeholder.com/100",
        ocrStatus: "Pending",
        faceMatch: "Not Matched",
        status: "Pending"
    }
];

// Load fundis into admin dashboard
function loadFundis() {
    let container = document.getElementById("fundi-list");

    // If no fundis left
    if (fundis.length === 0) {
        container.innerHTML = "<h2>No pending fundi requests</h2>";
        return;
    }

    container.innerHTML = "";

    fundis.forEach((fundi, index) => {
        container.innerHTML += `
            <div class="card">
                <p><b>Name:</b> ${fundi.name}</p>
                <p><b>Skill:</b> ${fundi.skill}</p>

                <p><b>Status:</b> ${fundi.status}</p>

                <p><b>OCR:</b> ${fundi.ocrStatus}</p>
                <p><b>Face Match:</b> ${fundi.faceMatch}</p>

                <p>ID Image:</p>
                <img src="${fundi.idImage}" width="100">

                <p>Face Image:</p>
                <img src="${fundi.faceImage}" width="100"><br><br>

                <button onclick="approve(${index})">Approve</button>
                <button onclick="reject(${index})">Reject</button>
            </div>
        `;
    });
}

// Approve fundi
function approve(index) {
    alert(fundis[index].name + " Approved");
    fundis.splice(index, 1);
    loadFundis();
}

// Reject fundi
function reject(index) {
    alert(fundis[index].name + " Rejected");
    fundis.splice(index, 1);
    loadFundis();
}

// Run when page loads
loadFundis();