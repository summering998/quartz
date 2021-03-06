<?php
namespace Quartz\Calendar;

use Quartz\Core\Calendar;
use Quartz\Core\DateBuilder;

/**
 * <p>
 * This implementation of the Calendar excludes a set of days of the week. You
 * may use it to exclude weekends for example. But you may define any day of
 * the week.  By default it excludes SATURDAY and SUNDAY.
 * </p>
 */
class WeeklyCalendar extends BaseCalendar
{
    const INSTANCE = 'weekly';

    /**
     * {@inheritdoc}
     */
    public function __construct(Calendar $baseCalendar = null, \DateTimeZone $timeZone = null)
    {
        parent::__construct(self::INSTANCE, $baseCalendar, $timeZone);

        $this->setDaysExcluded([
            DateBuilder::SATURDAY,
            DateBuilder::SUNDAY,
        ]);
    }

    /**
     * <p>
     * Get the array with the week days
     * </p>
     */
    public function getDaysExcluded()
    {
        return $this->getValue('excludeDays', []);
    }

    /**
     * <p>
     * Return true, if wday (see Calendar.get()) is defined to be exluded. E. g.
     * saturday and sunday.
     * </p>
     *
     * @param int $wday
     *
     * @return bool
     */
    public function isDayExcluded($wday)
    {
        DateBuilder::validateDayOfWeek($wday);

        $days = $this->getValue('excludeDays', []);

        return in_array($wday, $days, true);
    }

    /**
     * <p>
     * Redefine the array of days excluded. The array must of size greater or
     * equal 7. Calendar's constants like MONDAY should be used as
     * index. A value of true is regarded as: exclude it.
     * </p>
     *
     * @param array $weekDays
     */
    public function setDaysExcluded(array $weekDays)
    {
        foreach ($weekDays as $wday) {
            DateBuilder::validateDayOfWeek($wday);
        }

        $this->setValue('excludeDays', $weekDays);
    }

    /**
     * <p>
     * Redefine a certain day of the week to be excluded (true) or included
     * (false). Use Calendar's constants like MONDAY to determine the
     * wday.
     * </p>
     *
     * @param int  $wday
     * @param bool $exclude
     */
    public function setDayExcluded($wday, $exclude)
    {
        DateBuilder::validateDayOfWeek($wday);

        $days = $this->getValue('excludeDays', []);

        if ($exclude) {
            if (false === array_search($wday, $days, true)) {
                $days[] = $wday;
                sort($days, SORT_NUMERIC);
            }
        } else {
            if (false !== $index = array_search($wday, $days, true)) {
                unset($days[$index]);
                $days = array_values($days);
            }
        }

        $this->setValue('excludeDays', $days);
    }

    /**
     * <p>
     * Check if all week days are excluded. That is no day is included.
     * </p>
     *
     * @return boolean
     */
    public function areAllDaysExcluded()
    {
        return count($this->getValue('excludeDays', [])) >= 7;
    }

    /**
     * <p>
     * Determine whether the given time (in milliseconds) is 'included' by the
     * Calendar.
     * </p>
     *
     * <p>
     * Note that this Calendar is only has full-day precision.
     * </p>
     *
     * @param int $timeStamp
     *
     * @return bool
     */
    public function isTimeIncluded($timeStamp)
    {
        if ($this->getValue('excludeAll')) {
            return false;
        }

        // Test the base calendar first. Only if the base calendar not already
        // excludes the time/date, continue evaluating this calendar instance.
        if (false == parent::isTimeIncluded($timeStamp)) {
            return false;
        }

        $date = $this->createDateTime($timeStamp);
        $wday = (int) $date->format('N');

        return false == $this->isDayExcluded($wday);
    }

    /**
     * <p>
     * Determine the next time (in milliseconds) that is 'included' by the
     * Calendar after the given time. Return the original value if timeStamp is
     * included. Return 0 if all days are excluded.
     * </p>
     *
     * <p>
     * Note that this Calendar is only has full-day precision.
     * </p>
     *
     * @param int $timeStamp
     *
     * @return int
     */
    public function getNextIncludedTime($timeStamp)
    {
        if ($this->getValue('excludeAll')) {
            return 0;
        }

        // Call base calendar implementation first
        $baseTime = parent::getNextIncludedTime($timeStamp);
        if ($baseTime > 0 && $baseTime > $timeStamp) {
            $timeStamp = $baseTime;
        }

        // Get timestamp for 00:00:00
        $date = $this->getStartOfDayDateTime($timeStamp);
        $wday = (int) $date->format('N');

        if (false == $this->isDayExcluded($wday)) {
            return $timeStamp; // return the original value
        }

        while ($this->isDayExcluded($wday)) {
            $date->add(new \DateInterval('P1D'));
            $wday = (int) $date->format('N');
        }

        return (int) $date->format('U');
    }
}
