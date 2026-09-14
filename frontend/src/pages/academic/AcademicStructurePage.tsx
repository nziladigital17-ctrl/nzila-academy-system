import React, { useEffect, useState, useCallback } from 'react';
import { PageHeader } from '@/components/ui/PageHeader';
import { Tabs } from '@/components/ui/Tabs';
import { LoadingSkeleton } from '@/components/ui/LoadingSkeleton';
import { useAcademicYearStore } from '@/stores/academicYearStore';
import { hasPermission } from '@/stores/authStore';
import { getSubjects, getClasses, getTeacherAssignments } from '@/api/academicService';
import { AcademicYearsTab } from './tabs/AcademicYearsTab';
import { TermsTab } from './tabs/TermsTab';
import { SubjectsTab } from './tabs/SubjectsTab';
import { RoomsClassesTab } from './tabs/RoomsClassesTab';
import { TeacherAssignmentsTab } from './tabs/TeacherAssignmentsTab';

interface MetricCounts {
  yearCount: number;
  classCount: number;
  subjectCount: number;
  assignmentCount: number;
}

const TABS = [
  { id: 'years', label: 'Anos Lectivos', icon: '📅' },
  { id: 'terms', label: 'Trimestres', icon: '📆' },
  { id: 'subjects', label: 'Disciplinas', icon: '📖' },
  { id: 'rooms-classes', label: 'Salas & Turmas', icon: '🏫' },
  { id: 'assignments', label: 'Atribuições', icon: '👨‍🏫' },
];

export const AcademicStructurePage: React.FC = () => {
  const { years, selectedYearId, isLoading: yearsLoading, fetchYears } = useAcademicYearStore();
  const [activeTab, setActiveTab] = useState('years');
  const [metrics, setMetrics] = useState<MetricCounts>({ yearCount: 0, classCount: 0, subjectCount: 0, assignmentCount: 0 });
  const [metricsLoading, setMetricsLoading] = useState(true);

  useEffect(() => {
    fetchYears();
  }, [fetchYears]);

  const loadMetrics = useCallback(async () => {
    if (!selectedYearId) return;
    setMetricsLoading(true);
    try {
      const [subjectsRes, classesRes, assignmentsRes] = await Promise.all([
        hasPermission('subjects.view') ? getSubjects({ page: 1 }) : null,
        hasPermission('classes.view') ? getClasses({ academic_year_id: selectedYearId, page: 1 }) : null,
        hasPermission('teaching_assignments.view') ? getTeacherAssignments({ academic_year_id: selectedYearId, page: 1 }) : null,
      ]);
      setMetrics({
        yearCount: years.length,
        subjectCount: subjectsRes?.meta?.total ?? 0,
        classCount: classesRes?.meta?.total ?? 0,
        assignmentCount: assignmentsRes?.meta?.total ?? 0,
      });
    } catch {
      // Silently fail metrics — non-critical
    } finally {
      setMetricsLoading(false);
    }
  }, [selectedYearId, years.length]);

  useEffect(() => {
    if (selectedYearId) {
      loadMetrics();
    }
  }, [selectedYearId, loadMetrics]);

  const selectedYear = years.find((y) => y.id === selectedYearId);

  const visibleTabs = TABS.filter((tab) => {
    if (tab.id === 'subjects' && !hasPermission('subjects.view')) return false;
    if (tab.id === 'rooms-classes' && !hasPermission('rooms.view') && !hasPermission('classes.view')) return false;
    if (tab.id === 'assignments' && !hasPermission('teaching_assignments.view')) return false;
    if (tab.id === 'terms' && !hasPermission('terms.view')) return false;
    return true;
  });

  if (yearsLoading && years.length === 0) {
    return (
      <div>
        <LoadingSkeleton variant="cards" />
        <div style={{ marginTop: 'var(--nz-space-6, 24px)' }}>
          <LoadingSkeleton variant="table" rows={8} columns={5} />
        </div>
      </div>
    );
  }

  return (
    <div>
      <PageHeader
        title="Estrutura Académica"
        subtitle={selectedYear ? `Ano Lectivo ${selectedYear.name}` : 'Gestão da estrutura académica da escola'}
      />

      {/* Metric Cards */}
      <div className="nz-metric-grid" style={{ marginBottom: 'var(--nz-space-6, 24px)' }}>
        <div className="nz-metric-card">
          <div className="nz-metric-card__icon" aria-hidden="true">📅</div>
          <div className="nz-metric-card__label">Anos Lectivos</div>
          <div className="nz-metric-card__value">
            {metricsLoading ? <div className="nz-skeleton" style={{ width: '48px', height: '28px' }} /> : metrics.yearCount}
          </div>
          <div className="nz-metric-card__footer">Configurados no sistema</div>
        </div>

        <div className="nz-metric-card nz-metric-card--gold">
          <div className="nz-metric-card__icon" aria-hidden="true">🏫</div>
          <div className="nz-metric-card__label">Turmas Activas</div>
          <div className="nz-metric-card__value">
            {metricsLoading ? <div className="nz-skeleton" style={{ width: '48px', height: '28px' }} /> : metrics.classCount}
          </div>
          <div className="nz-metric-card__footer">{selectedYear ? `${selectedYear.name}` : '—'}</div>
        </div>

        <div className="nz-metric-card">
          <div className="nz-metric-card__icon" aria-hidden="true">📖</div>
          <div className="nz-metric-card__label">Disciplinas</div>
          <div className="nz-metric-card__value">
            {metricsLoading ? <div className="nz-skeleton" style={{ width: '48px', height: '28px' }} /> : metrics.subjectCount}
          </div>
          <div className="nz-metric-card__footer">Total registadas</div>
        </div>

        <div className="nz-metric-card nz-metric-card--success">
          <div className="nz-metric-card__icon" aria-hidden="true">👨‍🏫</div>
          <div className="nz-metric-card__label">Docentes Atribuídos</div>
          <div className="nz-metric-card__value">
            {metricsLoading ? <div className="nz-skeleton" style={{ width: '48px', height: '28px' }} /> : metrics.assignmentCount}
          </div>
          <div className="nz-metric-card__footer">{selectedYear ? `${selectedYear.name}` : '—'}</div>
        </div>
      </div>

      {/* Tabs */}
      <Tabs tabs={visibleTabs} activeTab={activeTab} onTabChange={setActiveTab} />

      {/* Tab Content */}
      <div role="tabpanel" id={`tabpanel-${activeTab}`} aria-labelledby={`tab-${activeTab}`}>
        {activeTab === 'years' && <AcademicYearsTab onDataChange={loadMetrics} />}
        {activeTab === 'terms' && <TermsTab />}
        {activeTab === 'subjects' && <SubjectsTab onDataChange={loadMetrics} />}
        {activeTab === 'rooms-classes' && <RoomsClassesTab onDataChange={loadMetrics} />}
        {activeTab === 'assignments' && <TeacherAssignmentsTab onDataChange={loadMetrics} />}
      </div>
    </div>
  );
};
