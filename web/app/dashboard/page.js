'use client';

import { useEffect } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import {
  AlertTriangle,
  BarChart3,
  CalendarClock,
  CheckCircle2,
  ClipboardList,
  DollarSign,
  FileText,
  Leaf,
  LogOut,
  MapPinned,
  PackageCheck,
  PhoneCall,
  ShieldCheck,
  Stethoscope,
  TrendingUp,
  Users,
  Video,
} from 'lucide-react';
import { useAuth } from '../context/AuthContext';

const executiveKpis = [
  ['Registered Users', '12,480', '+18% this month', Users],
  ['Total Scans', '46,920', 'Crop 62% / Livestock 38%', BarChart3],
  ['Treatment Success', '84%', '+6% after expert review', CheckCircle2],
  ['Revenue', 'NGN 8.7M', 'Subscriptions, market, consults', DollarSign],
];

const reportTemplates = [
  'Monthly business performance',
  'Disease surveillance by LGA',
  'Expert consultation analytics',
  'Marketplace transaction summary',
  'User engagement and retention',
];

const pendingApprovals = [
  ['Dr. Ibrahim Musa', 'Veterinary Doctor', 'Small ruminants', 'Katsina Central'],
  ['Aisha Bello', 'Agronomist', 'Crop protection', 'Funtua'],
  ['Sarkin Maska Agro Inputs', 'Agro-dealer', 'Seeds and medication', 'Daura'],
];

const expertCases = {
  vet: [
    ['Emergency', 'Goat stool scan', 'Possible coccidiosis, bloody diarrhea', 'Call farmer now'],
    ['Urgent', 'Cattle visual case', 'Tick fever signs, high temperature', 'Review images'],
    ['Monitor', 'Sheep appetite change', 'Low activity, no visible discharge', 'Ask follow-up'],
  ],
  agronomist: [
    ['Urgent', 'Tomato leaf scan', 'Late blight suspected, wet weather risk', 'Recommend spray plan'],
    ['Monitor', 'Maize deficiency', 'Yellow lower leaves, nitrogen deficiency', 'Check fertilizer'],
    ['Routine', 'Millet pest damage', 'Mild leaf chewing damage', 'Advise IPM'],
  ],
};

function DashboardShell({ user, logout, children }) {
  const router = useRouter();

  return (
    <div className="dashboard-page">
      <aside className="dash-sidebar">
        <Link className="dash-brand" href="/">
          <span className="dash-brand-mark">MS</span>
          <span>
            <strong>MSAS FarmAI</strong>
            <small>{user.role === 'admin' ? 'CEO Command Center' : 'Expert Workspace'}</small>
          </span>
        </Link>
        <nav className="dash-nav" aria-label="Dashboard navigation">
          {['Overview', 'Consultations', 'Reports', 'Users', 'Marketplace', 'Settings'].map((item) => (
            <a href={`#${item.toLowerCase()}`} key={item}>{item}</a>
          ))}
        </nav>
        <button
          type="button"
          className="dash-logout"
          onClick={() => {
            logout();
            router.push('/');
          }}
        >
          <LogOut size={17} /> Logout
        </button>
      </aside>

      <div className="dash-main">
        <header className="dash-topbar">
          <div>
            <p>Welcome back</p>
            <h1>{user.name}</h1>
          </div>
          <div className="dash-user-pill">
            <ShieldCheck size={18} />
            <span>{user.role}</span>
          </div>
        </header>
        {children}
      </div>
    </div>
  );
}

function KpiCard({ label, value, note, Icon }) {
  return (
    <article className="dash-card kpi-card">
      <Icon size={24} />
      <span>{label}</span>
      <strong>{value}</strong>
      <small>{note}</small>
    </article>
  );
}

function CeoDashboard() {
  return (
    <>
      <section id="overview" className="dash-hero-panel">
        <div>
          <p>Executive summary</p>
          <h2>Platform progress, health, revenue, and farmer impact in one place.</h2>
        </div>
        <div className="dash-hero-actions">
          <a href="#reports"><FileText size={17} /> Generate Report</a>
          <a href="#approvals"><CheckCircle2 size={17} /> Review Approvals</a>
        </div>
      </section>

      <section className="dash-kpi-grid" aria-label="CEO KPIs">
        {executiveKpis.map(([label, value, note, Icon]) => (
          <KpiCard key={label} label={label} value={value} note={note} Icon={Icon} />
        ))}
      </section>

      <section className="dash-grid two">
        <article className="dash-card">
          <div className="dash-card-title">
            <TrendingUp size={20} />
            <h3>Growth & Engagement</h3>
          </div>
          <div className="chart-bars" aria-label="Monthly user growth chart">
            {[42, 58, 64, 72, 81, 92, 100].map((height, index) => (
              <span key={index} style={{ height: `${height}%` }} />
            ))}
          </div>
          <p className="dash-muted">User acquisition is ahead of target. Weekly active usage is strongest in Katsina, Funtua, and Daura.</p>
        </article>

        <article className="dash-card">
          <div className="dash-card-title">
            <MapPinned size={20} />
            <h3>Disease Surveillance</h3>
          </div>
          <div className="risk-list">
            {[
              ['Funtua', 'Fall armyworm', 'High'],
              ['Daura', 'Foot and mouth alerts', 'Medium'],
              ['Katsina Central', 'Tomato late blight', 'High'],
            ].map(([region, issue, risk]) => (
              <div key={region}>
                <span>{region}</span>
                <strong>{issue}</strong>
                <em>{risk}</em>
              </div>
            ))}
          </div>
        </article>
      </section>

      <section id="approvals" className="dash-grid two">
        <article className="dash-card">
          <div className="dash-card-title">
            <Users size={20} />
            <h3>Pending Verification</h3>
          </div>
          <div className="dash-table">
            {pendingApprovals.map(([name, role, specialty, location]) => (
              <div key={name}>
                <strong>{name}</strong>
                <span>{role}</span>
                <span>{specialty}</span>
                <span>{location}</span>
                <button type="button">Review</button>
              </div>
            ))}
          </div>
        </article>

        <article id="reports" className="dash-card">
          <div className="dash-card-title">
            <FileText size={20} />
            <h3>Report Builder</h3>
          </div>
          <div className="report-list">
            {reportTemplates.map((template) => (
              <button type="button" key={template}>
                <FileText size={16} /> {template}
              </button>
            ))}
          </div>
          <div className="report-controls">
            <select aria-label="Report date range" defaultValue="30">
              <option value="7">Last 7 days</option>
              <option value="30">Last 30 days</option>
              <option value="90">Last quarter</option>
            </select>
            <button type="button">Export PDF</button>
            <button type="button">Export CSV</button>
          </div>
        </article>
      </section>

      <section className="dash-grid three">
        {[
          ['Platform Health', '99.9% uptime, 180ms API average', ShieldCheck],
          ['Support Resolution', '92% resolved within 24 hours', ClipboardList],
          ['Marketplace Quality', 'Seeds and medication approvals active', PackageCheck],
        ].map(([title, copy, Icon]) => (
          <article className="dash-card mini-panel" key={title}>
            <Icon size={22} />
            <h3>{title}</h3>
            <p>{copy}</p>
          </article>
        ))}
      </section>
    </>
  );
}

function ExpertDashboard({ role }) {
  const isVet = role === 'vet';
  const cases = isVet ? expertCases.vet : expertCases.agronomist;

  return (
    <>
      <section className="dash-hero-panel expert">
        <div>
          <p>{isVet ? 'Veterinary doctor workspace' : 'Agronomist workspace'}</p>
          <h2>{isVet ? 'Review livestock cases, call farmers, and prescribe safe treatment.' : 'Protect crops through fast diagnosis review and input guidance.'}</h2>
        </div>
        <div className="dash-hero-actions">
          <a href="#queue"><PhoneCall size={17} /> Start Calls</a>
          <a href="#availability"><CalendarClock size={17} /> Set Availability</a>
        </div>
      </section>

      <section className="dash-kpi-grid">
        {[
          ['Open Cases', '18', 'Emergency first', AlertTriangle],
          ['Completed Consults', '146', '+12 this week', CheckCircle2],
          ['Average Rating', '4.8/5', 'Farmer satisfaction', ShieldCheck],
          ['Earnings', 'NGN 420K', 'Available for payout', DollarSign],
        ].map(([label, value, note, Icon]) => (
          <KpiCard key={label} label={label} value={value} note={note} Icon={Icon} />
        ))}
      </section>

      <section id="queue" className="dash-grid two">
        <article className="dash-card">
          <div className="dash-card-title">
            {isVet ? <Stethoscope size={20} /> : <Leaf size={20} />}
            <h3>Consultation Queue</h3>
          </div>
          <div className="case-list">
            {cases.map(([urgency, title, summary, action]) => (
              <div key={title}>
                <span className={`urgency ${urgency.toLowerCase()}`}>{urgency}</span>
                <strong>{title}</strong>
                <p>{summary}</p>
                <button type="button">{action}</button>
              </div>
            ))}
          </div>
        </article>

        <article className="dash-card">
          <div className="dash-card-title">
            <Video size={20} />
            <h3>Direct Consultation Tools</h3>
          </div>
          <div className="tool-stack">
            {['Voice call with farmer', 'Video review of scan images', 'Prescription and dosage note', 'Follow-up appointment', 'Medication or seed quality recommendation'].map((item) => (
              <div key={item}><CheckCircle2 size={16} /> {item}</div>
            ))}
          </div>
        </article>
      </section>
    </>
  );
}

function FarmerDashboard() {
  return (
    <>
      <section className="dash-hero-panel farmer">
        <div>
          <p>Farmer dashboard</p>
          <h2>Scan sick crops or animals, get a treatment plan, then call an expert when you need help.</h2>
        </div>
        <div className="dash-hero-actions">
          <Link href="/#services"><Leaf size={17} /> Scan Plant</Link>
          <Link href="/#contact-us"><PhoneCall size={17} /> Call Expert</Link>
        </div>
      </section>
      <section className="dash-grid three">
        {[
          ['Crop Scans', '12 scans saved this season', Leaf],
          ['Livestock Cases', '3 animals under monitoring', Stethoscope],
          ['Treatment Reminders', '2 follow-ups due this week', CalendarClock],
        ].map(([title, copy, Icon]) => (
          <article className="dash-card mini-panel" key={title}>
            <Icon size={22} />
            <h3>{title}</h3>
            <p>{copy}</p>
          </article>
        ))}
      </section>
    </>
  );
}

export default function Dashboard() {
  const { user, logout, loading } = useAuth();
  const router = useRouter();

  useEffect(() => {
    if (!loading && !user) router.push('/');
  }, [user, loading, router]);

  if (loading || !user) {
    return (
      <div className="dashboard-loading">
        <span>Loading dashboard...</span>
      </div>
    );
  }

  return (
    <DashboardShell user={user} logout={logout}>
      {user.role === 'admin' && <CeoDashboard />}
      {(user.role === 'vet' || user.role === 'agronomist') && <ExpertDashboard role={user.role} />}
      {!['admin', 'vet', 'agronomist'].includes(user.role) && <FarmerDashboard />}
    </DashboardShell>
  );
}
