import { Component, OnInit, AfterViewInit } from '@angular/core';
import { Chart } from 'chart.js/auto';
import { DashboardService } from '../../../services/dashboard.service';
import { CommonModule } from '@angular/common';
import { catchError } from 'rxjs/operators';
import { throwError } from 'rxjs';

// Define interfaces for the API responses
interface Statistics {
  total_commissions: number;
  total_properties: number;
  total_users: number;
  net_profit: number;
}

interface MonthlyData {
  month: string;
  properties_sold: number;
  profit_margin: string;
  new_users: number;
  added_properties: number;
}

interface Property {
  title: string;
  price: number;
  city: string;
  listing_type: 'for_sale' | 'for_rent';
  user: { first_name: string; last_name: string };
  created_at: string;
}

@Component({
  selector: 'app-admin-statistics',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './admin-statistics.component.html',
  styleUrls: ['./admin-statistics.component.css']
})
export class AdminStatisticsComponent implements OnInit, AfterViewInit {
  statistics: Statistics | string | null = null;
  monthlyData: MonthlyData[] = [];
  latestProperties: Property[] | string = [];
  private salesChart: Chart | null = null;
  private profitTrendChart: Chart | null = null;
  private usersPropertiesChart: Chart | null = null;
  loadingStatistics: boolean = false;

  constructor(private dashboardService: DashboardService) {}

  ngOnInit(): void {
    console.log('ngOnInit: Starting to fetch admin statistics data');
    this.fetchDashboardData();
  }

  ngAfterViewInit(): void {
    console.log('ngAfterViewInit: DOM should be ready, canvas elements should be available');
  }

  // Type guard to check if statistics is a Statistics object
  isStatistics(value: Statistics | string | null): value is Statistics {
    return value !== null && typeof value !== 'string';
  }

  isArray(value: any): value is any[] {
    return Array.isArray(value);
  }

  fetchDashboardData(): void {
    this.loadingStatistics = true;
    this.dashboardService.getStatistics()
      .pipe(
        catchError(error => {
          console.error('Error fetching statistics:', error);
          this.statistics = 'Error';
          this.loadingStatistics = false;
          return throwError(() => error);
        })
      )
      .subscribe((data: { statistics: Statistics }) => {
        console.log('Statistics fetched:', data);
        this.statistics = data.statistics;
        this.loadingStatistics = false;
      });

    this.dashboardService.getLatestProperties()
      .pipe(
        catchError(error => {
          console.error('Error fetching latest properties:', error);
          this.latestProperties = 'Error';
          return throwError(() => error);
        })
      )
      .subscribe((data: { latest_properties: Property[] }) => {
        console.log('Latest properties fetched:', data);
        this.latestProperties = data.latest_properties || [];
      });

    this.dashboardService.getMonthlyData()
      .pipe(
        catchError(error => {
          console.error('Error fetching monthly data:', error);
          this.monthlyData = [];
          console.log('After error, monthlyData set to:', this.monthlyData);
          this.renderCharts();
          return throwError(() => error);
        })
      )
      .subscribe((data: { monthly_data: MonthlyData[] }) => {
        console.log('Raw API response for monthly data:', data);
        this.monthlyData = data.monthly_data || [];
        console.log('Processed monthlyData:', this.monthlyData);
        this.renderCharts();
      });
  }

  private renderCharts(): void {
    setTimeout(() => {
      console.log('Rendering charts with monthlyData:', this.monthlyData);
      this.renderSalesChart();
      this.renderProfitTrendChart();
      this.renderUsersPropertiesChart();
    }, 100);
  }

  renderSalesChart(): void {
    const salesCtx = document.getElementById('myChart') as HTMLCanvasElement;
    if (!salesCtx) {
      console.error('Sales chart canvas not found');
      return;
    }
    const existingChart = Chart.getChart('myChart');
    if (existingChart) existingChart.destroy();

    const labels = this.monthlyData.length ? this.monthlyData.map(item => item.month) : ['Nov', 'Dec', 'Jan', 'Feb', 'Mar'];
    const data = this.monthlyData.length ? this.monthlyData.map(item => item.properties_sold) : [0, 0, 0, 0, 0];

    this.salesChart = new Chart(salesCtx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: 'Properties Sold',
          data: data,
          backgroundColor: 'rgba(54, 162, 235, 0.5)',
          borderColor: 'rgba(54, 162, 235, 1)',
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: true, position: 'top' } }
      }
    });
  }

  renderProfitTrendChart(): void {
    const lineCtx = document.getElementById('lineChart') as HTMLCanvasElement;
    if (!lineCtx) {
      console.error('Profit trend chart canvas not found');
      return;
    }
    const existingChart = Chart.getChart('lineChart');
    if (existingChart) existingChart.destroy();

    const labels = this.monthlyData.length ? this.monthlyData.map(item => item.month) : ['Nov', 'Dec', 'Jan', 'Feb', 'Mar'];
    const data = this.monthlyData.length ? this.monthlyData.map(item => parseFloat(item.profit_margin.replace('%', ''))) : [0, 0, 0, 0, 0];

    this.profitTrendChart = new Chart(lineCtx, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [{
          label: 'Profit Margin (%)',
          data: data,
          backgroundColor: 'rgba(75, 192, 192, 0.2)',
          borderColor: 'rgba(75, 192, 192, 1)',
          borderWidth: 2,
          tension: 0.1,
          fill: true
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: true, position: 'top' } },
        scales: { y: { beginAtZero: true, title: { display: true, text: 'Profit Margin (%)' } } }
      }
    });
  }

  renderUsersPropertiesChart(): void {
    const usersPropertiesCtx = document.getElementById('usersPropertiesChart') as HTMLCanvasElement;
    if (!usersPropertiesCtx) {
      console.error('Users vs Properties chart canvas not found');
      return;
    }
    const existingChart = Chart.getChart('usersPropertiesChart');
    if (existingChart) existingChart.destroy();

    const labels = this.monthlyData.length ? this.monthlyData.map(item => item.month) : ['Nov', 'Dec', 'Jan', 'Feb', 'Mar'];
    const newUsersData = this.monthlyData.length ? this.monthlyData.map(item => item.new_users) : [0, 0, 0, 0, 0];
    const addedPropertiesData = this.monthlyData.length ? this.monthlyData.map(item => item.added_properties) : [0, 0, 0, 0, 0];

    this.usersPropertiesChart = new Chart(usersPropertiesCtx, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'New Users',
            data: newUsersData,
            borderColor: '#FF6F61',
            backgroundColor: 'rgba(255, 111, 97, 0.2)',
            borderWidth: 3,
            tension: 0.3,
            fill: false,
            pointRadius: 5,
            pointBackgroundColor: '#FF6F61',
            pointBorderColor: '#fff',
            pointBorderWidth: 2
          },
          {
            label: 'Added Properties',
            data: addedPropertiesData,
            borderColor: '#6B5B95',
            backgroundColor: 'rgba(107, 91, 149, 0.2)',
            borderWidth: 3,
            tension: 0.3,
            fill: false,
            pointRadius: 5,
            pointBackgroundColor: '#6B5B95',
            pointBorderColor: '#fff',
            pointBorderWidth: 2
          }
        ]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: true, position: 'top', labels: { font: { size: 14 }, color: '#333' } },
          title: { display: true, text: 'New Users vs Added Properties Over Time', font: { size: 16 }, color: '#333' }
        },
        scales: {
          y: { beginAtZero: true, title: { display: true, text: 'Count', font: { size: 14 }, color: '#333' } },
          x: { title: { display: true, text: 'Month', font: { size: 14 }, color: '#333' } }
        }
      }
    });
  }
}