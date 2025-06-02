// Sample data - In a real application, this would come from a database
const dashboardData = {
    totalAppointments: 45,
    dailyRevenue: 1250,
    productsSold: 23,
    recentAppointments: [
        { client: "John Doe", service: "Haircut", date: "2023-10-15", time: "10:00 AM", status: "Completed" },
        { client: "Mike Johnson", service: "Beard Trim", date: "2023-10-15", time: "11:30 AM", status: "Completed" },
        { client: "Robert Smith", service: "Full Service", date: "2023-10-15", time: "1:15 PM", status: "In Progress" },
        { client: "Alex Williams", service: "Hair Coloring", date: "2023-10-15", time: "3:00 PM", status: "Scheduled" },
        { client: "David Brown", service: "Shave", date: "2023-10-15", time: "4:30 PM", status: "Scheduled" }
    ]
};

// Function to update dashboard metrics
function updateDashboardMetrics() {
    document.getElementById('appointment-count').textContent = dashboardData.totalAppointments;
    document.getElementById('revenue-count').textContent = `$${dashboardData.dailyRevenue}`;
    document.getElementById('products-sold-count').textContent = dashboardData.productsSold;
    
    // Update appointments table
    const tableBody = document.getElementById('appointments-table-body');
    tableBody.innerHTML = '';
    
    dashboardData.recentAppointments.forEach(appointment => {
        const row = document.createElement('tr');
        
        // Add status class for styling
        let statusClass = '';
        switch(appointment.status.toLowerCase()) {
            case 'completed':
                statusClass = 'status-completed';
                break;
            case 'in progress':
                statusClass = 'status-progress';
                break;
            case 'scheduled':
                statusClass = 'status-scheduled';
                break;
            default:
                statusClass = '';
        }
        
        row.innerHTML = `
            <td>${appointment.client}</td>
            <td>${appointment.service}</td>
            <td>${appointment.date}</td>
            <td>${appointment.time}</td>
            <td class="${statusClass}">${appointment.status}</td>
        `;
        
        tableBody.appendChild(row);
    });
}

// Initialize the dashboard
document.addEventListener('DOMContentLoaded', () => {
    updateDashboardMetrics();
    
    // In a real application, you might want to refresh data periodically
    // setInterval(fetchDashboardData, 60000); // Refresh every minute
});

// This function would fetch fresh data from the server in a real application
function fetchDashboardData() {
    // Example AJAX request
    // fetch('/api/dashboard-data')
    //     .then(response => response.json())
    //     .then(data => {
    //         dashboardData = data;
    //         updateDashboardMetrics();
    //     })
    //     .catch(error => console.error('Error fetching dashboard data:', error));
}
