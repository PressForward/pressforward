<?php

namespace GenderEngine\Matchers;

use GenderEngine\Gender;
use GenderEngine\Matchers\Interfaces\Matcher;

class RegExpV1Match implements Matcher
{
    private static $nameRegExp = [
        // most names ending in a/e/i/y are female
        '.*[aeiy]'                             => Gender::FEMALE,
        // allison and variations
        'all?[iy]((ss?)|z)on'                  => Gender::FEMALE,
        // cathleen, eileen, maureen
        'een'                                  => Gender::FEMALE,
        // barry, larry, perry
        '[^s].*r[rv]e?y?'                      => Gender::MALE,
        // clive, dave, steve
        '[^g].*v[ei]'                          => Gender::MALE,
        // carolyn, gwendolyn, vivian
        '[^bd].*(b[iy]|y|via)nn?'              => Gender::FEMALE,
        // dewey, stanley, wesley
        '[^ajklmnp][^o][^eit]*([glrsw]ey|lie)' => Gender::MALE,
        // heather, ruth, velvet
        '[^gksw].*(th|lv)(e[rt])?'             => Gender::FEMALE,
        // gregory, jeremy, zachary
        '[cgjwz][^o][^dnt]*y'                  => Gender::MALE,
        // leroy, murray, roy
        '.*[rlr][abo]y'                        => Gender::MALE,
        // abigail, jill, lillian
        '[aehjl].*il.*'                        => Gender::FEMALE,
        // janet, jennifer, joan
        '.*[jj](o|o?[ae]a?n.*)'                => Gender::FEMALE,
        // duane, eugene, rene
        '.*[grguw][ae]y?ne'                    => Gender::MALE,
        // fleur, lauren, muriel
        '[flm].*ur(.*[^eotuy])?'               => Gender::FEMALE,
        // lance, quincy, vince
        '[clmqtv].*[^dl][in]c.*[ey]'           => Gender::MALE,
        // margaret, marylou, miri
        'm[aei]r[^tv].*([^cklnos]|([^o]n))'    => Gender::FEMALE,
        // clyde, kyle, pascale
        '.*[ay][dl]e'                          => Gender::MALE,
        // blake, luke, mi,
        '[^o]*ke'                              => Gender::MALE,
        // carol, karen, shar,
        '[cks]h?(ar[^lst]|ry).+'               => Gender::FEMALE,
        // pam, pearl, rachel
        '[pr]e?a([^dfju]|qu)*[lm]'             => Gender::FEMALE,
        // annacarol, leann, ruthann
        '.*[aa]nn.*'                           => Gender::FEMALE,
        // deborah, leah, sarah
        '.*[^cio]ag?h'                         => Gender::FEMALE,
        // frances, megan, susan
        '[^ek].*[grsz]h?an(ces)?'              => Gender::FEMALE,
        // ethel, helen, gretchen
        '[^p]*([hh]e|[ee][lt])[^s]*[ey].*[^t]' => Gender::FEMALE,
        // george, joshua, theodore
        '[^el].*o(rg?|sh?)?(e|ua)'             => Gender::MALE,
        // delores, doris, precious
        '[dp][eo]?[lr].*s'                     => Gender::FEMALE,
        // anthony, henry, rodney
        '[^jpswz].*[denor]n.*y'                => Gender::MALE,
        // karin, kim, kristin
        'k[^v]*i.*[mns]'                       => Gender::FEMALE,
        // bradley, brady, bruce
        'br[aou][cd].*[ey]'                    => Gender::MALE,
        // agnes, alexis, glynis
        '[acgk].*[deinx][^aor]s'               => Gender::FEMALE,
        // ignace, lee, wallace
        '[ilw][aeg][^ir]*e'                    => Gender::MALE,
        // juliet, mildred, millicent
        '[^agw][iu][gl].*[drt]'                => Gender::FEMALE,
        // ari, bela, ira
        '[abeiuy][euz]?[blr][aeiy]'            => Gender::MALE,
        // iris, lois, phyllis
        '[egilp][^eu]*i[ds]'                   => Gender::FEMALE,
        // randy, timothy, tony
        '[art][^r]*[dhn]e?y'                   => Gender::MALE,
        // beatriz, bridget, harriet
        '[bhl].*i.*[rtxz]'                     => Gender::FEMALE,
        // antoine, jerome, tyrone
        '.*oi?[mn]e'                           => Gender::MALE,
        // danny, demetri, dondi
        'd.*[mnw].*[iy]'                       => Gender::MALE,
        // pete, serge, shane
        '[^bg](e[rst]|ha)[^il]*e'              => Gender::MALE,
        // angel, gail, isabel
        '[adfgim][^r]*([bg]e[lr]|il|wn)'       => Gender::FEMALE
    ];

    public function test($name)
    {
        $genderGuess = Gender::UNKNOWN;

        foreach (self::$nameRegExp as $regExp => $gender) {
            if (preg_match('/^' . $regExp . '$/', $name)) {
                $genderGuess = $gender;
            }
        }

        return $genderGuess;
    }
}

