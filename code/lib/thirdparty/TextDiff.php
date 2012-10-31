<?php


class Text_Diff_Engine_native
{
    function diff($from_lines, $to_lines)
    {
        array_walk($from_lines, array(
            'Text_Diff',
            'trimNewlines'
        ));
        array_walk($to_lines, array(
            'Text_Diff',
            'trimNewlines'
        ));
        $n_from         = count($from_lines);
        $n_to           = count($to_lines);
        $this->xchanged = $this->ychanged = array();
        $this->xv       = $this->yv = array();
        $this->xind     = $this->yind = array();
        unset($this->seq);
        unset($this->in_seq);
        unset($this->lcs);
        for ($skip = 0; $skip < $n_from && $skip < $n_to; $skip++) {
            if ($from_lines[$skip] !== $to_lines[$skip]) {
                break;
            }
            $this->xchanged[$skip] = $this->ychanged[$skip] = false;
        }
        $xi = $n_from;
        $yi = $n_to;
        for ($endskip = 0; --$xi > $skip && --$yi > $skip; $endskip++) {
            if ($from_lines[$xi] !== $to_lines[$yi]) {
                break;
            }
            $this->xchanged[$xi] = $this->ychanged[$yi] = false;
        }
        for ($xi = $skip; $xi < $n_from - $endskip; $xi++) {
            $xhash[$from_lines[$xi]] = 1;
        }
        for ($yi = $skip; $yi < $n_to - $endskip; $yi++) {
            $line = $to_lines[$yi];
            if (($this->ychanged[$yi] = empty($xhash[$line]))) {
                continue;
            }
            $yhash[$line] = 1;
            $this->yv[]   = $line;
            $this->yind[] = $yi;
        }
        for ($xi = $skip; $xi < $n_from - $endskip; $xi++) {
            $line = $from_lines[$xi];
            if (($this->xchanged[$xi] = empty($yhash[$line]))) {
                continue;
            }
            $this->xv[]   = $line;
            $this->xind[] = $xi;
        }
        $this->_compareseq(0, count($this->xv), 0, count($this->yv));
        $this->_shiftBoundaries($from_lines, $this->xchanged, $this->ychanged);
        $this->_shiftBoundaries($to_lines, $this->ychanged, $this->xchanged);
        $edits = array();
        $xi    = $yi = 0;
        while ($xi < $n_from || $yi < $n_to) {
            assert($yi < $n_to || $this->xchanged[$xi]);
            assert($xi < $n_from || $this->ychanged[$yi]);
            $copy = array();
            while ($xi < $n_from && $yi < $n_to && !$this->xchanged[$xi] && !$this->ychanged[$yi]) {
                $copy[] = $from_lines[$xi++];
                ++$yi;
            }
            if ($copy) {
                $edits[] = new Text_Diff_Op_copy($copy);
            }
            $delete = array();
            while ($xi < $n_from && $this->xchanged[$xi]) {
                $delete[] = $from_lines[$xi++];
            }
            $add = array();
            while ($yi < $n_to && $this->ychanged[$yi]) {
                $add[] = $to_lines[$yi++];
            }
            if ($delete && $add) {
                $edits[] = new Text_Diff_Op_change($delete, $add);
            } elseif ($delete) {
                $edits[] = new Text_Diff_Op_delete($delete);
            } elseif ($add) {
                $edits[] = new Text_Diff_Op_add($add);
            }
        }
        return $edits;
    }
    function _diag($xoff, $xlim, $yoff, $ylim, $nchunks)
    {
        $flip = false;
        if ($xlim - $xoff > $ylim - $yoff) {
            $flip = true;
            list($xoff, $xlim, $yoff, $ylim) = array(
                $yoff,
                $ylim,
                $xoff,
                $xlim
            );
        }
        if ($flip) {
            for ($i = $ylim - 1; $i >= $yoff; $i--) {
                $ymatches[$this->xv[$i]][] = $i;
            }
        } else {
            for ($i = $ylim - 1; $i >= $yoff; $i--) {
                $ymatches[$this->yv[$i]][] = $i;
            }
        }
        $this->lcs    = 0;
        $this->seq[0] = $yoff - 1;
        $this->in_seq = array();
        $ymids[0]     = array();
        $numer        = $xlim - $xoff + $nchunks - 1;
        $x            = $xoff;
        for ($chunk = 0; $chunk < $nchunks; $chunk++) {
            if ($chunk > 0) {
                for ($i = 0; $i <= $this->lcs; $i++) {
                    $ymids[$i][$chunk - 1] = $this->seq[$i];
                }
            }
            $x1 = $xoff + (int) (($numer + ($xlim - $xoff) * $chunk) / $nchunks);
            for (; $x < $x1; $x++) {
                $line = $flip ? $this->yv[$x] : $this->xv[$x];
                if (empty($ymatches[$line])) {
                    continue;
                }
                $matches = $ymatches[$line];
                reset($matches);
                while (list(, $y) = each($matches)) {
                    if (empty($this->in_seq[$y])) {
                        $k = $this->_lcsPos($y);
                        assert($k > 0);
                        $ymids[$k] = $ymids[$k - 1];
                        break;
                    }
                }
                while (list(, $y) = each($matches)) {
                    if ($y > $this->seq[$k - 1]) {
                        assert($y <= $this->seq[$k]);
                        $this->in_seq[$this->seq[$k]] = false;
                        $this->seq[$k]                = $y;
                        $this->in_seq[$y]             = 1;
                    } elseif (empty($this->in_seq[$y])) {
                        $k = $this->_lcsPos($y);
                        assert($k > 0);
                        $ymids[$k] = $ymids[$k - 1];
                    }
                }
            }
        }
        $seps[] = $flip ? array(
            $yoff,
            $xoff
        ) : array(
            $xoff,
            $yoff
        );
        $ymid   = $ymids[$this->lcs];
        for ($n = 0; $n < $nchunks - 1; $n++) {
            $x1     = $xoff + (int) (($numer + ($xlim - $xoff) * $n) / $nchunks);
            $y1     = $ymid[$n] + 1;
            $seps[] = $flip ? array(
                $y1,
                $x1
            ) : array(
                $x1,
                $y1
            );
        }
        $seps[] = $flip ? array(
            $ylim,
            $xlim
        ) : array(
            $xlim,
            $ylim
        );
        return array(
            $this->lcs,
            $seps
        );
    }
    function _lcsPos($ypos)
    {
        $end = $this->lcs;
        if ($end == 0 || $ypos > $this->seq[$end]) {
            $this->seq[++$this->lcs] = $ypos;
            $this->in_seq[$ypos]     = 1;
            return $this->lcs;
        }
        $beg = 1;
        while ($beg < $end) {
            $mid = (int) (($beg + $end) / 2);
            if ($ypos > $this->seq[$mid]) {
                $beg = $mid + 1;
            } else {
                $end = $mid;
            }
        }
        assert($ypos != $this->seq[$end]);
        $this->in_seq[$this->seq[$end]] = false;
        $this->seq[$end]                = $ypos;
        $this->in_seq[$ypos]            = 1;
        return $end;
    }
    function _compareseq($xoff, $xlim, $yoff, $ylim)
    {
        while ($xoff < $xlim && $yoff < $ylim && $this->xv[$xoff] == $this->yv[$yoff]) {
            ++$xoff;
            ++$yoff;
        }
        while ($xlim > $xoff && $ylim > $yoff && $this->xv[$xlim - 1] == $this->yv[$ylim - 1]) {
            --$xlim;
            --$ylim;
        }
        if ($xoff == $xlim || $yoff == $ylim) {
            $lcs = 0;
        } else {
            $nchunks = min(7, $xlim - $xoff, $ylim - $yoff) + 1;
            list($lcs, $seps) = $this->_diag($xoff, $xlim, $yoff, $ylim, $nchunks);
        }
        if ($lcs == 0) {
            while ($yoff < $ylim) {
                $this->ychanged[$this->yind[$yoff++]] = 1;
            }
            while ($xoff < $xlim) {
                $this->xchanged[$this->xind[$xoff++]] = 1;
            }
        } else {
            reset($seps);
            $pt1 = $seps[0];
            while ($pt2 = next($seps)) {
                $this->_compareseq($pt1[0], $pt2[0], $pt1[1], $pt2[1]);
                $pt1 = $pt2;
            }
        }
    }
    function _shiftBoundaries($lines, &$changed, $other_changed)
    {
        $i = 0;
        $j = 0;
        assert('count($lines) == count($changed)');
        $len       = count($lines);
        $other_len = count($other_changed);
        while (1) {
            while ($j < $other_len && $other_changed[$j]) {
                $j++;
            }
            while ($i < $len && !$changed[$i]) {
                assert('$j < $other_len && ! $other_changed[$j]');
                $i++;
                $j++;
                while ($j < $other_len && $other_changed[$j]) {
                    $j++;
                }
            }
            if ($i == $len) {
                break;
            }
            $start = $i;
            while (++$i < $len && $changed[$i]) {
                continue;
            }
            do {
                $runlength = $i - $start;
                while ($start > 0 && $lines[$start - 1] == $lines[$i - 1]) {
                    $changed[--$start] = 1;
                    $changed[--$i]     = false;
                    while ($start > 0 && $changed[$start - 1]) {
                        $start--;
                    }
                    assert('$j > 0');
                    while ($other_changed[--$j]) {
                        continue;
                    }
                    assert('$j >= 0 && !$other_changed[$j]');
                }
                $corresponding = $j < $other_len ? $i : $len;
                while ($i < $len && $lines[$start] == $lines[$i]) {
                    $changed[$start++] = false;
                    $changed[$i++]     = 1;
                    while ($i < $len && $changed[$i]) {
                        $i++;
                    }
                    assert('$j < $other_len && ! $other_changed[$j]');
                    $j++;
                    if ($j < $other_len && $other_changed[$j]) {
                        $corresponding = $i;
                        while ($j < $other_len && $other_changed[$j]) {
                            $j++;
                        }
                    }
                }
            } while ($runlength != $i - $start);
            while ($corresponding < $i) {
                $changed[--$start] = 1;
                $changed[--$i]     = 0;
                assert('$j > 0');
                while ($other_changed[--$j]) {
                    continue;
                }
                assert('$j >= 0 && !$other_changed[$j]');
            }
        }
    }
}
class Text_Diff
{
    var $_edits;
    function Text_Diff($engine, $params)
    {
        if (!is_string($engine)) {
            $params = array(
                $engine,
                $params
            );
            $engine = 'auto';
        }
        if ($engine == 'auto') {
            $engine = extension_loaded('xdiff') ? 'xdiff' : 'native';
        } else {
            $engine = basename($engine);
        }
        $class        = 'Text_Diff_Engine_' . $engine;
        $diff_engine  = new $class();
        $this->_edits = call_user_func_array(array(
            $diff_engine,
            'diff'
        ), $params);
    }
    function getDiff()
    {
        return $this->_edits;
    }
    function countAddedLines()
    {
        $count = 0;
        foreach ($this->_edits as $edit) {
            if (is_a($edit, 'Text_Diff_Op_add') || is_a($edit, 'Text_Diff_Op_change')) {
                $count += $edit->nfinal();
            }
        }
        return $count;
    }
    function countDeletedLines()
    {
        $count = 0;
        foreach ($this->_edits as $edit) {
            if (is_a($edit, 'Text_Diff_Op_delete') || is_a($edit, 'Text_Diff_Op_change')) {
                $count += $edit->norig();
            }
        }
        return $count;
    }
    function reverse()
    {
        if (version_compare(zend_version(), '2', '>')) {
            $rev = clone ($this);
        } else {
            $rev = $this;
        }
        $rev->_edits = array();
        foreach ($this->_edits as $edit) {
            $rev->_edits[] = $edit->reverse();
        }
        return $rev;
    }
    function isEmpty()
    {
        foreach ($this->_edits as $edit) {
            if (!is_a($edit, 'Text_Diff_Op_copy')) {
                return false;
            }
        }
        return true;
    }
    function lcs()
    {
        $lcs = 0;
        foreach ($this->_edits as $edit) {
            if (is_a($edit, 'Text_Diff_Op_copy')) {
                $lcs += count($edit->orig);
            }
        }
        return $lcs;
    }
    function getOriginal()
    {
        $lines = array();
        foreach ($this->_edits as $edit) {
            if ($edit->orig) {
                array_splice($lines, count($lines), 0, $edit->orig);
            }
        }
        return $lines;
    }
    function getFinal()
    {
        $lines = array();
        foreach ($this->_edits as $edit) {
            if ($edit->final) {
                array_splice($lines, count($lines), 0, $edit->final);
            }
        }
        return $lines;
    }
    static function trimNewlines(&$line, $key)
    {
        $line = str_replace(array(
            "\n",
            "\r"
        ), '', $line);
    }
    function _getTempDir()
    {
        $tmp_locations = array(
            '/tmp',
            '/var/tmp',
            'c:\WUTemp',
            'c:\temp',
            'c:\windows\temp',
            'c:\winnt\temp'
        );
        $tmp           = ini_get('upload_tmp_dir');
        if (!strlen($tmp)) {
            $tmp = getenv('TMPDIR');
        }
        while (!strlen($tmp) && count($tmp_locations)) {
            $tmp_check = array_shift($tmp_locations);
            if (@is_dir($tmp_check)) {
                $tmp = $tmp_check;
            }
        }
        return strlen($tmp) ? $tmp : false;
    }
    function _check($from_lines, $to_lines)
    {
        if (serialize($from_lines) != serialize($this->getOriginal())) {
            trigger_error("Reconstructed original doesn't match", E_USER_ERROR);
        }
        if (serialize($to_lines) != serialize($this->getFinal())) {
            trigger_error("Reconstructed final doesn't match", E_USER_ERROR);
        }
        $rev = $this->reverse();
        if (serialize($to_lines) != serialize($rev->getOriginal())) {
            trigger_error("Reversed original doesn't match", E_USER_ERROR);
        }
        if (serialize($from_lines) != serialize($rev->getFinal())) {
            trigger_error("Reversed final doesn't match", E_USER_ERROR);
        }
        $prevtype = null;
        foreach ($this->_edits as $edit) {
            if ($prevtype == get_class($edit)) {
                trigger_error("Edit sequence is non-optimal", E_USER_ERROR);
            }
            $prevtype = get_class($edit);
        }
        return true;
    }
}
class Text_MappedDiff extends Text_Diff
{
    function Text_MappedDiff($from_lines, $to_lines, $mapped_from_lines, $mapped_to_lines)
    {
        assert(count($from_lines) == count($mapped_from_lines));
        assert(count($to_lines) == count($mapped_to_lines));
        parent::Text_Diff($mapped_from_lines, $mapped_to_lines);
        $xi = $yi = 0;
        for ($i = 0; $i < count($this->_edits); $i++) {
            $orig = $this->_edits[$i]->orig;
            if (is_array($orig)) {
                $orig = array_slice($from_lines, $xi, count($orig));
                $xi += count($orig);
            }
            $final = $this->_edits[$i]->final;
            if (is_array($final)) {
                $final = array_slice($to_lines, $yi, count($final));
                $yi += count($final);
            }
        }
    }
}
class Text_Diff_Op
{
    var $orig;
    var $final;
    function &reverse()
    {
        trigger_error('Abstract method', E_USER_ERROR);
    }
    function norig()
    {
        return $this->orig ? count($this->orig) : 0;
    }
    function nfinal()
    {
        return $this->final ? count($this->final) : 0;
    }
}
class Text_Diff_Op_copy extends Text_Diff_Op
{
    function Text_Diff_Op_copy($orig, $final = false)
    {
        if (!is_array($final)) {
            $final = $orig;
        }
        $this->orig  = $orig;
        $this->final = $final;
    }
    function &reverse()
    {
        $reverse = new Text_Diff_Op_copy($this->final, $this->orig);
        return $reverse;
    }
}
class Text_Diff_Op_delete extends Text_Diff_Op
{
    function Text_Diff_Op_delete($lines)
    {
        $this->orig  = $lines;
        $this->final = false;
    }
    function &reverse()
    {
        $reverse = new Text_Diff_Op_add($this->orig);
        return $reverse;
    }
}
class Text_Diff_Op_add extends Text_Diff_Op
{
    function Text_Diff_Op_add($lines)
    {
        $this->final = $lines;
        $this->orig  = false;
    }
    function &reverse()
    {
        $reverse = new Text_Diff_Op_delete($this->final);
        return $reverse;
    }
}
class Text_Diff_Op_change extends Text_Diff_Op
{
    function Text_Diff_Op_change($orig, $final)
    {
        $this->orig  = $orig;
        $this->final = $final;
    }
    function &reverse()
    {
        $reverse = new Text_Diff_Op_change($this->final, $this->orig);
        return $reverse;
    }
}
